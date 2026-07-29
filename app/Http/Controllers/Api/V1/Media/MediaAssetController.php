<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Domains\Media\Enums\MediaStatus;
use App\Domains\Media\Enums\MediaType;
use App\Domains\Media\Services\MediaCatalogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaAssetController extends Controller
{
    public function __construct(
        private readonly MediaCatalogService $media,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'media_type' => ['nullable', 'string', 'in:'.implode(',', MediaType::values())],
            'status' => ['nullable', 'string', 'in:'.implode(',', MediaStatus::values())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'mode' => ['nullable', 'string'],
        ]);

        if (($filters['mode'] ?? null) === 'all') {
            return response()->json($this->media->list($filters));
        }

        return response()->json($this->media->paginate($filters, $filters['per_page'] ?? 15));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'media_type' => ['required', 'string', 'in:'.implode(',', MediaType::values())],
            'file_path' => ['required', 'string', 'max:2048'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:'.implode(',', MediaStatus::values())],
        ]);

        return response()->json(
            $this->media->create($payload, $request->user()?->id),
            201,
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $mediaAsset = $this->media->findByUuid($uuid);
        abort_if($mediaAsset === null, 404, 'Media asset not found.');

        return response()->json($mediaAsset);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $mediaAsset = $this->media->findByUuid($uuid);
        abort_if($mediaAsset === null, 404, 'Media asset not found.');

        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'media_type' => ['required', 'string', 'in:'.implode(',', MediaType::values())],
            'file_path' => ['required', 'string', 'max:2048'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:'.implode(',', MediaStatus::values())],
        ]);

        return response()->json(
            $this->media->update($mediaAsset, $payload, $request->user()?->id),
        );
    }

    public function destroy(string $uuid): JsonResponse
    {
        $mediaAsset = $this->media->findByUuid($uuid);
        abort_if($mediaAsset === null, 404, 'Media asset not found.');

        $this->media->delete($mediaAsset);

        return response()->json(status: 204);
    }

    public function browser(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'path' => ['nullable', 'string', 'max:2048'],
        ]);

        return response()->json($this->media->browse($payload['path'] ?? null));
    }

    public function previewFolderImport(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'path' => ['required', 'string', 'max:2048'],
            'recursive' => ['nullable', 'boolean'],
        ]);

        return response()->json($this->media->previewImportFolder(
            $payload['path'],
            (bool) ($payload['recursive'] ?? false),
        ));
    }

    public function importFolder(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'path' => ['required', 'string', 'max:2048'],
            'recursive' => ['nullable', 'boolean'],
            'ignore_duplicates' => ['nullable', 'boolean'],
        ]);

        return response()->json(
            $this->media->importFolder(
                $payload['path'],
                (bool) ($payload['recursive'] ?? false),
                (bool) ($payload['ignore_duplicates'] ?? true),
                $request->user()?->id,
            ),
            201,
        );
    }

    public function uploadSingle(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'file' => ['required', 'file'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'media_type' => ['nullable', 'string', 'in:'.implode(',', MediaType::values())],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'in:'.implode(',', MediaStatus::values())],
        ]);

        unset($payload['file']);

        return response()->json(
            $this->media->importUploadedFiles(
                [$request->file('file')],
                $request->user()?->id,
                $payload,
            ),
            201,
        );
    }

    public function uploadFolder(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file'],
            'durations' => ['nullable', 'array'],
            'durations.*' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json(
            $this->media->importUploadedFiles(
                (array) $request->file('files', []),
                $request->user()?->id,
                durations: $payload['durations'] ?? [],
            ),
            201,
        );
    }

    public function preview(string $uuid): BinaryFileResponse
    {
        $mediaAsset = $this->media->findByUuid($uuid);
        abort_if($mediaAsset === null, 404, 'Media asset not found.');

        return $this->media->preview($mediaAsset);
    }
}
