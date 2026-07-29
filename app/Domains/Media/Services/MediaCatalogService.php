<?php

namespace App\Domains\Media\Services;

use App\Domains\Media\Enums\MediaStatus;
use App\Domains\Media\Enums\MediaType;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Media\Repositories\Contracts\MediaAssetRepositoryInterface;
use App\Domains\System\Services\SystemSettingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class MediaCatalogService
{
    public function __construct(
        private readonly MediaAssetRepositoryInterface $mediaAssets,
        private readonly MediaBrowserService $browser,
        private readonly SystemSettingService $settings,
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->mediaAssets->paginate($filters, $perPage);
    }

    public function list(array $filters = []): array
    {
        return $this->mediaAssets->list($filters)->all();
    }

    public function findByUuid(string $uuid): ?MediaAsset
    {
        return $this->mediaAssets->findByUuid($uuid);
    }

    public function create(array $payload, ?int $actorId = null): MediaAsset
    {
        $payload['uuid'] = (string) Str::uuid();
        $payload['created_by'] = $actorId;
        $payload['updated_by'] = $actorId;
        $payload['file_path'] = $this->browser->storePathReference($payload['file_path']);

        return $this->mediaAssets->create($payload);
    }

    public function update(MediaAsset $mediaAsset, array $payload, ?int $actorId = null): MediaAsset
    {
        if (array_key_exists('file_path', $payload)) {
            $payload['file_path'] = $this->browser->storePathReference($payload['file_path']);
        }

        $payload['updated_by'] = $actorId;

        return $this->mediaAssets->update($mediaAsset, $payload);
    }

    public function delete(MediaAsset $mediaAsset): void
    {
        $this->mediaAssets->delete($mediaAsset);
    }

    public function browse(?string $path = null): array
    {
        return $this->browser->browse($path);
    }

    public function preview(MediaAsset $mediaAsset): BinaryFileResponse
    {
        $path = $this->browser->ensurePreviewable($mediaAsset->rawFilePath());
        $mime = File::mimeType($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    public function previewImportFolder(string $path, bool $recursive = false): array
    {
        $files = collect($this->browser->scanDirectory($path, $recursive));

        return [
            'directory' => $path,
            'recursive' => $recursive,
            'files' => $this->mapImportCandidates($files),
        ];
    }

    public function importFolder(string $path, bool $recursive = false, bool $ignoreDuplicates = true, ?int $actorId = null): array
    {
        $files = collect($this->browser->scanDirectory($path, $recursive));
        $candidates = $this->mapImportCandidates($files);
        $created = [];
        $skipped = [];

        foreach ($candidates as $candidate) {
            if ($candidate['is_duplicate']) {
                $skipped[] = $candidate;

                if ($ignoreDuplicates) {
                    continue;
                }
            }

            $created[] = $this->create([
                'title' => $candidate['title'],
                'description' => null,
                'media_type' => $candidate['media_type'],
                'file_path' => $candidate['file_path'],
                'duration_seconds' => null,
                'status' => MediaStatus::READY->value,
            ], $actorId);
        }

        return [
            'directory' => $path,
            'recursive' => $recursive,
            'imported_count' => count($created),
            'skipped_count' => count($skipped),
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    public function importUploadedFiles(
        array $files,
        ?int $actorId = null,
        array $metadata = [],
        array $durations = [],
    ): array {
        $created = [];
        $destination = $this->settings->getMediaRoot()
            .DIRECTORY_SEPARATOR.'imports'
            .DIRECTORY_SEPARATOR.now()->format('Y')
            .DIRECTORY_SEPARATOR.now()->format('m')
            .DIRECTORY_SEPARATOR.now()->format('d');

        File::ensureDirectoryExists($destination);

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
            $uploadedFile = $file->move($destination, $this->uniqueImportedFilename($originalName));
            $absolutePath = $uploadedFile->getPathname();

            try {
                $created[] = $this->create([
                    'title' => $metadata['title'] ?? pathinfo($originalName, PATHINFO_FILENAME),
                    'description' => $metadata['description'] ?? null,
                    'media_type' => $metadata['media_type'] ?? $this->guessMediaType($file->getClientOriginalExtension()),
                    'file_path' => $absolutePath,
                    'duration_seconds' => $metadata['duration_seconds'] ?? $durations[$index] ?? null,
                    'status' => $metadata['status'] ?? MediaStatus::READY->value,
                ], $actorId);
            } catch (Throwable $exception) {
                File::delete($absolutePath);

                throw $exception;
            }
        }

        return [
            'imported_count' => count($created),
            'created' => $created,
        ];
    }

    private function mapImportCandidates(Collection $files): array
    {
        return $files->map(function (array $file): array {
            $storedReference = $this->browser->storePathReference($file['path']);
            $existing = MediaAsset::query()->where('file_path', $storedReference)->first();

            return [
                'title' => pathinfo($file['name'], PATHINFO_FILENAME),
                'file_name' => $file['name'],
                'file_path' => $this->browser->presentPath($storedReference),
                'storage_reference' => $storedReference,
                'extension' => $file['extension'],
                'media_type' => $this->guessMediaType($file['extension']),
                'status' => MediaStatus::READY->value,
                'is_duplicate' => $existing !== null,
                'existing_uuid' => $existing?->uuid,
                'size_bytes' => $file['size_bytes'] ?? null,
                'modified_at' => $file['modified_at'] ?? null,
            ];
        })->values()->all();
    }

    private function guessMediaType(string $extension): string
    {
        return match (strtolower($extension)) {
            'mp3', 'wav', 'aac', 'm4a' => MediaType::JINGLE->value,
            'jpg', 'jpeg', 'png' => MediaType::LIVE_PLACEHOLDER->value,
            default => MediaType::PROGRAM->value,
        };
    }

    private function uniqueImportedFilename(string $originalName): string
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $slug = Str::slug($name) ?: 'media';

        $filename = sprintf('%s-%s', $slug, Str::lower(Str::random(8)));

        return $extension !== '' ? $filename.'.'.strtolower($extension) : $filename;
    }
}
