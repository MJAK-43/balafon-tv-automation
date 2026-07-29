<?php

namespace App\Domains\Branding\Services;

use App\Domains\Branding\Enums\BrandingAssetType;
use App\Domains\Branding\Models\BrandingAsset;
use App\Domains\Media\Services\MediaBrowserService;
use App\Domains\System\Services\SystemSettingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BrandingAssetService
{
    public function __construct(
        private readonly MediaBrowserService $browser,
        private readonly SystemSettingService $settings,
    ) {}

    public function list(array $filters = []): array
    {
        return BrandingAsset::query()
            ->when($filters['asset_type'] ?? null, fn ($query, string $type) => $query->where('asset_type', $type))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('asset_type')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function findByUuid(string $uuid): ?BrandingAsset
    {
        return BrandingAsset::query()->where('uuid', $uuid)->first();
    }

    public function createLogo(
        UploadedFile $file,
        string $name,
        string $position = 'TOP_RIGHT',
        int $scale = 20,
        ?int $actorId = null,
    ): BrandingAsset {
        return $this->createFileAsset(
            $file,
            $name,
            BrandingAssetType::LOGO,
            'logos',
            true,
            $actorId,
            [
                'logo_position' => $position,
                'logo_scale' => $scale,
            ],
        );
    }

    public function createTextAnnouncement(
        string $name,
        string $text,
        bool $loopEnabled = true,
        string $textColor = '#FFFFFF',
        string $textBackgroundColor = '#07101D',
        string $textFont = 'SEGOE_UI',
        int $tickerSpeed = 120,
        ?int $actorId = null,
    ): BrandingAsset {
        return BrandingAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'asset_type' => BrandingAssetType::ANNOUNCEMENT_TEXT->value,
            'file_path' => null,
            'text_content' => $text,
            'text_color' => strtoupper($textColor),
            'text_background_color' => strtoupper($textBackgroundColor),
            'text_font' => $textFont,
            'ticker_speed' => $tickerSpeed,
            'loop_enabled' => $loopEnabled,
            'status' => 'ACTIVE',
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);
    }

    public function createVideoAnnouncement(
        UploadedFile $file,
        string $name,
        bool $loopEnabled = true,
        ?int $actorId = null,
    ): BrandingAsset {
        return $this->createFileAsset(
            $file,
            $name,
            BrandingAssetType::ANNOUNCEMENT_VIDEO,
            'announcements',
            $loopEnabled,
            $actorId,
        );
    }

    public function update(BrandingAsset $asset, array $payload, ?int $actorId = null): BrandingAsset
    {
        $attributes = [
            'name' => $payload['name'],
            'text_content' => $asset->asset_type === BrandingAssetType::ANNOUNCEMENT_TEXT->value
                ? $payload['text_content']
                : null,
            'loop_enabled' => $payload['loop_enabled'] ?? $asset->loop_enabled,
            'status' => $payload['status'],
            'updated_by' => $actorId,
        ];

        if ($asset->asset_type === BrandingAssetType::LOGO->value) {
            $attributes['logo_position'] = $payload['logo_position'] ?? $asset->logo_position;
            $attributes['logo_scale'] = $payload['logo_scale'] ?? $asset->logo_scale;
        }

        if ($asset->asset_type === BrandingAssetType::ANNOUNCEMENT_TEXT->value) {
            $attributes['text_color'] = strtoupper($payload['text_color'] ?? $asset->text_color);
            $attributes['text_background_color'] = strtoupper(
                $payload['text_background_color'] ?? $asset->text_background_color
            );
            $attributes['text_font'] = $payload['text_font'] ?? $asset->text_font;
            $attributes['ticker_speed'] = $payload['ticker_speed'] ?? $asset->ticker_speed;
        }

        $asset->update($attributes);

        return $asset->refresh();
    }

    public function delete(BrandingAsset $asset): void
    {
        if ($asset->logoPlaylistItems()->exists() || $asset->announcementPlaylistItems()->exists()) {
            throw ValidationException::withMessages([
                'asset' => 'This graphic is still used by one or more playlists.',
            ]);
        }

        $path = $asset->rawFilePath();
        $asset->delete();

        if ($path !== null) {
            File::delete($this->browser->resolveStoragePath($path));
        }
    }

    public function preview(BrandingAsset $asset): BinaryFileResponse
    {
        $path = $asset->rawFilePath();

        if ($path === null) {
            throw new RuntimeException('This text announcement has no preview file.');
        }

        $path = $this->browser->ensurePreviewable($path);
        $mime = File::mimeType($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    private function createFileAsset(
        UploadedFile $file,
        string $name,
        BrandingAssetType $type,
        string $directory,
        bool $loopEnabled,
        ?int $actorId,
        array $attributes = [],
    ): BrandingAsset {
        $destination = $this->settings->getMediaRoot()
            .DIRECTORY_SEPARATOR.'graphics'
            .DIRECTORY_SEPARATOR.$directory;
        File::ensureDirectoryExists($destination);

        $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = sprintf(
            '%s-%s%s',
            Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: strtolower($type->value),
            Str::lower(Str::random(8)),
            $extension !== '' ? '.'.$extension : '',
        );
        $uploadedFile = $file->move($destination, $filename);
        $absolutePath = $uploadedFile->getPathname();

        try {
            return BrandingAsset::query()->create(array_merge([
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'asset_type' => $type->value,
                'file_path' => $this->browser->storePathReference($absolutePath),
                'text_content' => null,
                'loop_enabled' => $loopEnabled,
                'status' => 'ACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ], $attributes));
        } catch (Throwable $exception) {
            File::delete($absolutePath);

            throw $exception;
        }
    }
}
