<?php

namespace App\Domains\System\Services;

use App\Domains\System\Models\SystemSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SystemSettingService
{
    private const MEDIA_ROOT_KEY = 'media_root';

    public function get(string $key, ?string $default = null): ?string
    {
        try {
            return SystemSetting::query()->where('key', $key)->value('value') ?? $default;
        } catch (QueryException) {
            return $default;
        }
    }

    public function set(string $key, string $value): void
    {
        try {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        } catch (QueryException) {
            // Ignore until migrations are applied; the runtime can still use the value in-memory.
        }
    }

    public function getMediaRoot(): string
    {
        $root = trim((string) $this->get(self::MEDIA_ROOT_KEY, ''));

        if ($root === '') {
            $root = $this->defaultMediaRoot();
            $this->set(self::MEDIA_ROOT_KEY, $root);
        }

        File::ensureDirectoryExists($root);

        return $root;
    }

    public function setMediaRoot(string $path): string
    {
        $path = trim($path);

        $this->assertUsableMediaRoot($path);

        File::ensureDirectoryExists($path);
        $this->set(self::MEDIA_ROOT_KEY, $path);

        return $path;
    }

    private function defaultMediaRoot(): string
    {
        if (app()->environment('testing')) {
            return storage_path('app/media-library');
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $programData = getenv('PROGRAMDATA');

            if (is_string($programData) && trim($programData) !== '') {
                return rtrim($programData, '\\/').'\\Balafon\\Media';
            }

            return 'C:\\BalafonMedia';
        }

        return storage_path('app/media-library');
    }

    private function assertUsableMediaRoot(string $path): void
    {
        if ($path === '') {
            throw new RuntimeException('The media root path cannot be empty.');
        }

        if (PHP_OS_FAMILY !== 'Windows' && preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            throw new RuntimeException(
                'This Balafon instance is running in Docker/Linux and cannot use a Windows path directly. Move your media into the Balafon media folder or mount that Windows folder into the container first.'
            );
        }
    }
}
