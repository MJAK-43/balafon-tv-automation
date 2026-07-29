<?php

namespace App\Domains\Media\Services;

use App\Domains\System\Services\SystemSettingService;
use FilesystemIterator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class MediaBrowserService
{
    private const ROOT_REFERENCE_PREFIX = 'media://';

    private const SUPPORTED_EXTENSIONS = [
        'mp4',
        'mov',
        'mxf',
        'avi',
        'mkv',
        'mp3',
        'wav',
        'aac',
        'm4a',
        'jpg',
        'jpeg',
        'png',
    ];

    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function roots(): array
    {
        $containerRoots = $this->configuredContainerRoots();
        $hostRoots = $this->configuredDisplayRoots($containerRoots);

        return array_map(function (string $path, int $index) use ($hostRoots): array {
            $displayPath = $hostRoots[$index] ?? $path;

            return [
                'name' => $displayPath,
                'path' => $displayPath,
            ];
        }, $containerRoots, array_keys($containerRoots));
    }

    public function browse(?string $path = null): array
    {
        if ($path === null || trim($path) === '') {
            return [
                'current_path' => null,
                'parent_path' => null,
                'roots' => $this->roots(),
                'directories' => [],
                'files' => [],
            ];
        }

        $normalized = $this->normalizePath($this->translateToLocalPath($path));
        $this->ensureAllowed($normalized);

        if (! is_dir($normalized)) {
            throw new RuntimeException('The selected directory does not exist.');
        }

        $entries = collect(scandir($normalized) ?: [])
            ->reject(fn (string $entry): bool => in_array($entry, ['.', '..'], true))
            ->map(fn (string $entry): SplFileInfo => new SplFileInfo($normalized.DIRECTORY_SEPARATOR.$entry));

        return [
            'current_path' => $this->translateToDisplayPath($normalized),
            'parent_path' => $this->translateToDisplayPath($this->parentPath($normalized)),
            'roots' => $this->roots(),
            'directories' => $this->mapDirectories($entries),
            'files' => $this->mapFiles($entries),
        ];
    }

    public function ensurePreviewable(string $path): string
    {
        $normalized = $this->normalizePath($this->resolveStoragePath($path));
        $this->ensureAllowed($normalized);

        if (! is_file($normalized)) {
            throw new RuntimeException('The selected media file does not exist.');
        }

        return $normalized;
    }

    public function storePathReference(string $path): string
    {
        $localPath = $this->ensurePreviewable($path);

        foreach ($this->pathMappings() as $index => $mapping) {
            if (! str_starts_with($this->normalizeForComparison($localPath), $this->normalizeForComparison($mapping['local']))) {
                continue;
            }

            $relativePath = ltrim(substr($localPath, strlen($mapping['local'])), '\\/');

            return self::ROOT_REFERENCE_PREFIX.$index.'/'.str_replace('\\', '/', $relativePath);
        }

        return $localPath;
    }

    public function resolveStoragePath(string $path): string
    {
        if (! str_starts_with($path, self::ROOT_REFERENCE_PREFIX)) {
            return $this->translateToLocalPath($path) ?? $path;
        }

        $relative = substr($path, strlen(self::ROOT_REFERENCE_PREFIX));
        [$index, $suffix] = array_pad(explode('/', $relative, 2), 2, '');
        $mapping = $this->pathMappings()[(int) $index] ?? null;

        if ($mapping === null) {
            throw new RuntimeException('The selected media root is not configured on this machine.');
        }

        $suffix = str_replace('/', DIRECTORY_SEPARATOR, $suffix);

        return rtrim($mapping['local'], '\\/').($suffix !== '' ? DIRECTORY_SEPARATOR.$suffix : '');
    }

    public function presentPath(string $path): string
    {
        if (! str_starts_with($path, self::ROOT_REFERENCE_PREFIX)) {
            return $this->translateToDisplayPath($path) ?? $path;
        }

        $relative = substr($path, strlen(self::ROOT_REFERENCE_PREFIX));
        [$index, $suffix] = array_pad(explode('/', $relative, 2), 2, '');
        $mapping = $this->pathMappings()[(int) $index] ?? null;

        if ($mapping === null) {
            return $path;
        }

        $displayRoot = rtrim($mapping['display'], '\\/');

        if ($suffix === '') {
            return $displayRoot;
        }

        $separator = str_contains($displayRoot, '\\') ? '\\' : '/';

        return $displayRoot.$separator.str_replace('/', $separator, $suffix);
    }

    public function scanDirectory(string $path, bool $recursive = false): array
    {
        $normalized = $this->normalizePath($this->translateToLocalPath($path));
        $this->ensureAllowed($normalized);

        if (! is_dir($normalized)) {
            throw new RuntimeException('The selected directory does not exist.');
        }

        return $recursive
            ? $this->scanRecursive($normalized)
            : $this->mapFiles(collect(scandir($normalized) ?: [])
                ->reject(fn (string $entry): bool => in_array($entry, ['.', '..'], true))
                ->map(fn (string $entry): SplFileInfo => new SplFileInfo($normalized.DIRECTORY_SEPARATOR.$entry))
            );
    }

    private function mapDirectories(Collection $entries): array
    {
        return $entries
            ->filter(fn (SplFileInfo $entry): bool => $entry->isDir())
            ->sortBy(fn (SplFileInfo $entry): string => strtolower($entry->getFilename()))
            ->map(fn (SplFileInfo $entry): array => [
                'name' => $entry->getFilename(),
                'path' => $this->translateToDisplayPath($entry->getPathname()),
            ])
            ->values()
            ->all();
    }

    private function mapFiles(Collection $entries): array
    {
        return $entries
            ->filter(fn (SplFileInfo $entry): bool => $entry->isFile() && $this->isSupportedExtension($entry->getExtension()))
            ->sortBy(fn (SplFileInfo $entry): string => strtolower($entry->getFilename()))
            ->map(fn (SplFileInfo $entry): array => [
                'name' => $entry->getFilename(),
                'path' => $this->translateToDisplayPath($entry->getPathname()),
                'extension' => strtolower($entry->getExtension()),
                'size_bytes' => $entry->getSize(),
                'modified_at' => Carbon::createFromTimestamp($entry->getMTime())->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function scanRecursive(string $path): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        $entries = collect(iterator_to_array($iterator))
            ->filter(fn (SplFileInfo $entry): bool => $entry->isFile() && $this->isSupportedExtension($entry->getExtension()));

        return $this->mapFiles($entries);
    }

    private function isSupportedExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::SUPPORTED_EXTENSIONS, true);
    }

    private function parentPath(string $path): ?string
    {
        $parent = dirname($path);

        if ($parent === $path || $parent === '.' || $parent === '') {
            return null;
        }

        try {
            $this->ensureAllowed($parent);
        } catch (RuntimeException) {
            return null;
        }

        return $parent;
    }

    private function translateToLocalPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return $path;
        }

        foreach ($this->pathMappings() as $mapping) {
            if (str_starts_with($this->normalizeForComparison($path), $this->normalizeForComparison($mapping['display']))) {
                return $mapping['local'].substr($path, strlen($mapping['display']));
            }
        }

        return $path;
    }

    private function translateToDisplayPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return $path;
        }

        foreach ($this->pathMappings() as $mapping) {
            if (str_starts_with($this->normalizeForComparison($path), $this->normalizeForComparison($mapping['local']))) {
                return $mapping['display'].substr($path, strlen($mapping['local']));
            }
        }

        return $path;
    }

    private function pathMappings(): array
    {
        $containerRoots = $this->configuredContainerRoots();
        $hostRoots = $this->configuredDisplayRoots($containerRoots);

        return collect($containerRoots)
            ->map(fn (string $local, int $index): array => [
                'local' => rtrim($local, '\\/'),
                'display' => rtrim($hostRoots[$index] ?? $local, '\\/'),
            ])
            ->values()
            ->all();
    }

    private function ensureAllowed(string $path): void
    {
        $normalizedPath = $this->normalizeForComparison($path);

        foreach ($this->configuredContainerRoots() as $root) {
            $normalizedRoot = $this->normalizeForComparison($this->normalizePath($root));

            if (str_starts_with($normalizedPath, $normalizedRoot)) {
                return;
            }
        }

        throw new RuntimeException('The selected path is outside the configured media roots.');
    }

    private function normalizePath(string $path): string
    {
        $resolved = realpath($path);

        return $resolved !== false ? $resolved : $path;
    }

    private function normalizeForComparison(string $path): string
    {
        return str_replace('/', '\\', mb_strtolower(rtrim($path, '\\/')));
    }

    private function configuredContainerRoots(): array
    {
        if (app()->environment('testing')) {
            return [storage_path('app')];
        }

        $configuredRoots = config('balafon.media.browser_roots', []);
        $savedRoot = $this->settings->getMediaRoot();

        // In native Windows mode, the UI-configured media root is the real local scan root.
        if (PHP_OS_FAMILY === 'Windows' && $savedRoot !== '' && is_dir($savedRoot)) {
            return [$savedRoot];
        }

        if ($configuredRoots === []) {
            return [$savedRoot];
        }

        $primaryConfiguredRoot = $configuredRoots[0] ?? null;

        if (! is_string($primaryConfiguredRoot) || $primaryConfiguredRoot === '' || ! is_dir($primaryConfiguredRoot)) {
            return [$savedRoot];
        }

        return $configuredRoots;
    }

    private function configuredDisplayRoots(array $containerRoots): array
    {
        if (app()->environment('testing')) {
            return $containerRoots;
        }

        $savedRoot = $this->settings->getMediaRoot();

        if ($savedRoot !== '') {
            return [$savedRoot];
        }

        $configuredRoots = config('balafon.media.host_browser_roots', []);

        if ($configuredRoots !== []) {
            return $configuredRoots;
        }

        return $containerRoots;
    }
}
