<?php

namespace App\Domains\Automation\Services;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Automation\Models\BroadcastRun;
use App\Domains\Automation\Models\BroadcastRunItem;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Media\Services\MediaBrowserService;
use App\Domains\Vmix\Models\VmixCommandLog;
use App\Domains\Vmix\Models\VmixConnection;
use App\Domains\Vmix\Providers\Contracts\VmixProviderInterface;
use App\Domains\Vmix\Repositories\Contracts\VmixConnectionRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class VmixExecutionService
{
    public function __construct(
        private readonly VmixConnectionRepositoryInterface $connections,
        private readonly VmixProviderInterface $provider,
        private readonly AuditService $audit,
        private readonly MediaBrowserService $mediaBrowser,
    ) {}

    public function resolveConnection(): VmixConnection
    {
        $connection = $this->connections->allActive()->first();

        if ($connection === null) {
            throw new RuntimeException('No active vMix connection configured.');
        }

        return $connection;
    }

    public function prepareAndPlay(BroadcastRun $run, BroadcastRunItem $item): array
    {
        $snapshot = $this->prepareInput($run, $item);

        $this->playPreparedInput($run, $item, $snapshot);

        return $snapshot;
    }

    public function prepareInput(BroadcastRun $run, BroadcastRunItem $item): array
    {
        $connection = $run->connection;
        $media = $item->mediaAsset;

        $addResponse = $this->sendLoggedCommand($connection, $run, $item, 'AddInput', [
            'Value' => sprintf('%s|%s', $this->resolveInputType($media), $this->resolveVmixFilePath($media->file_path)),
        ]);

        if (($addResponse['status'] ?? 'failed') !== 'success') {
            throw new RuntimeException($addResponse['error_message'] ?? 'Failed to add input to vMix.');
        }

        $snapshot = $this->isMock()
            ? [
                'key' => 'mock-'.$item->id,
                'number' => (string) $item->sequence,
                'title' => $this->mediaFileName($media->file_path),
                'type' => $this->resolveInputType($media),
                'state' => 'Running',
                'position' => 0,
                'duration' => (int) (($media->duration_seconds ?? 0) * 1000),
            ]
            : $this->awaitInputSnapshot($connection, $this->mediaFileName($media->file_path));

        if ($snapshot === null) {
            throw new RuntimeException('vMix input could not be resolved after AddInput.');
        }

        if (in_array($this->resolveInputType($media), ['Video', 'AudioFile'], true)) {
            $input = $snapshot['key'] ?: $snapshot['number'];

            $this->requireSuccessful(
                $this->sendLoggedCommand($connection, $run, $item, 'Pause', ['Input' => $input]),
                'Failed to pause the prepared vMix input.',
            );
            $this->requireSuccessful(
                $this->sendLoggedCommand($connection, $run, $item, 'SetPosition', [
                    'Input' => $input,
                    'Value' => 0,
                ]),
                'Failed to rewind the prepared vMix input.',
            );
        }

        return $snapshot;
    }

    public function playPreparedInput(BroadcastRun $run, BroadcastRunItem $item, ?array $snapshot = null): void
    {
        $snapshot ??= [
            'key' => $item->vmix_input_key,
            'number' => $item->vmix_input_number,
        ];
        $input = $snapshot['key'] ?: ($snapshot['number'] ?? null);

        if ($input === null) {
            throw new RuntimeException('The prepared vMix input could not be resolved.');
        }

        $playResponse = $this->sendLoggedCommand($run->connection, $run, $item, 'Play', [
            'Input' => $input,
        ]);

        if (($playResponse['status'] ?? 'failed') !== 'success') {
            throw new RuntimeException($playResponse['error_message'] ?? 'Failed to start playback in vMix.');
        }

        // Start the hidden input first, then switch Program to a frame that is already available.
        $this->requireSuccessful(
            $this->sendLoggedCommand($run->connection, $run, $item, 'ActiveInput', [
                'Input' => $input,
            ]),
            'Failed to switch the prepared input to Program in vMix.',
        );
    }

    public function monitor(BroadcastRun $run, BroadcastRunItem $item): array
    {
        if ($this->isMock()) {
            return $this->mockSnapshot($item);
        }

        return $this->findInputSnapshot($run->connection, $this->mediaFileName($item->mediaAsset->file_path), $item->vmix_input_key, $item->vmix_input_number)
            ?? [
                'key' => $item->vmix_input_key,
                'number' => $item->vmix_input_number,
                'title' => $this->mediaFileName($item->mediaAsset->file_path),
                'type' => $this->resolveInputType($item->mediaAsset),
                'state' => 'Missing',
                'position' => $item->last_known_position_ms ?? 0,
                'duration' => $item->last_known_duration_ms ?? (($item->mediaAsset->duration_seconds ?? 0) * 1000),
            ];
    }

    public function resumePlayback(BroadcastRun $run, BroadcastRunItem $item): void
    {
        $this->sendLoggedCommand($run->connection, $run, $item, 'Play', [
            'Input' => $item->vmix_input_key ?: $item->vmix_input_number,
        ]);
    }

    public function removeInput(BroadcastRun $run, BroadcastRunItem $item): void
    {
        $inputIdentifier = $item->vmix_input_key ?: $item->vmix_input_number;

        if ($inputIdentifier === null) {
            return;
        }

        $this->sendLoggedCommand($run->connection, $run, $item, 'RemoveInput', [
            'Input' => $inputIdentifier,
        ]);
    }

    public function syncGraphics(BroadcastRun $run, BroadcastRunItem $item): void
    {
        $run->refresh();
        $desired = $item->context['graphics'] ?? [];
        $current = $run->context['graphics_state'] ?? [];

        $current['logo'] = $this->syncGraphicSlot(
            $run,
            $item,
            $desired['logo'] ?? null,
            $current['logo'] ?? null,
            1,
        );
        $current['announcement'] = $this->syncGraphicSlot(
            $run,
            $item,
            $desired['announcement'] ?? null,
            $current['announcement'] ?? null,
            2,
        );

        $this->persistGraphicsState($run, array_filter($current));
    }

    public function clearGraphics(BroadcastRun $run, BroadcastRunItem $item): void
    {
        $run->refresh();
        $current = $run->context['graphics_state'] ?? [];

        foreach ([1 => 'logo', 2 => 'announcement'] as $overlay => $slot) {
            if (isset($current[$slot])) {
                $this->hideAndRemoveGraphic($run, $item, $current[$slot], $overlay);
            }
        }

        $this->persistGraphicsState($run, []);
    }

    public function status(): array
    {
        $connection = $this->resolveConnection();

        return $this->provider->getStatus($connection)->toArray();
    }

    private function sendLoggedCommand(
        VmixConnection $connection,
        BroadcastRun $run,
        BroadcastRunItem $item,
        string $command,
        array $parameters = [],
    ): array {
        $response = $this->provider->sendCommand($connection, $command, $parameters);

        VmixCommandLog::create([
            'uuid' => (string) Str::uuid(),
            'vmix_connection_id' => $connection->id,
            'broadcast_run_id' => $run->id,
            'broadcast_run_item_id' => $item->id,
            'schedule_id' => $run->schedule_id,
            'command_name' => $command,
            'request_url' => $response['request_url'] ?? '',
            'request_payload' => ['command' => $command, 'parameters' => $parameters],
            'response_code' => $response['response_code'] ?? null,
            'response_body' => $response['response_body'] ?? null,
            'status' => $response['status'] ?? 'failed',
            'duration_ms' => $response['duration_ms'] ?? null,
            'error_message' => $response['error_message'] ?? null,
            'executed_at' => now(),
        ]);

        $this->audit->log(
            action: 'automation.vmix_command',
            entityType: 'broadcast_run',
            entityId: (string) $run->id,
            context: [
                'broadcast_run_item_id' => $item->id,
                'command' => $command,
                'parameters' => $parameters,
                'response' => $response,
            ],
        );

        return $response;
    }

    private function findInputSnapshot(
        VmixConnection $connection,
        string $expectedTitle,
        ?string $expectedKey = null,
        ?string $expectedNumber = null,
    ): ?array {
        $inputs = $this->inputSnapshots($connection);

        if ($expectedKey !== null) {
            $match = collect($inputs)->first(fn (array $input): bool => $input['key'] === $expectedKey);
            if ($match !== null) {
                return $match;
            }
        }

        if ($expectedNumber !== null) {
            $match = collect($inputs)->first(fn (array $input): bool => $input['number'] === $expectedNumber);
            if ($match !== null) {
                return $match;
            }
        }

        return collect($inputs)->last(fn (array $input): bool => $input['title'] === $expectedTitle);
    }

    private function awaitInputSnapshot(VmixConnection $connection, string $expectedTitle): ?array
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $snapshot = $this->findInputSnapshot($connection, $expectedTitle);

            if ($snapshot !== null) {
                return $snapshot;
            }

            usleep(250000);
        }

        return null;
    }

    private function resolveInputType(MediaAsset $mediaAsset): string
    {
        $extension = strtolower(pathinfo($mediaAsset->file_path, PATHINFO_EXTENSION));

        return match ($extension) {
            'wav', 'mp3', 'aac', 'm4a' => 'AudioFile',
            'png', 'jpg', 'jpeg', 'bmp' => 'Image',
            'ppt', 'pptx' => 'PowerPoint',
            default => 'Video',
        };
    }

    private function isMock(): bool
    {
        return config('balafon.vmix.driver') === 'mock';
    }

    private function mockSnapshot(BroadcastRunItem $item): array
    {
        $duration = (int) (($item->mediaAsset->duration_seconds ?? 1) * 1000);
        $startedAt = $item->started_at ?? Carbon::now();
        $elapsed = max(0, (int) $startedAt->diffInMilliseconds(now(), false));
        $position = min($elapsed, $duration);

        return [
            'key' => $item->vmix_input_key ?? 'mock-'.$item->id,
            'number' => $item->vmix_input_number ?? '1',
            'title' => $this->mediaFileName($item->mediaAsset->file_path),
            'type' => $this->resolveInputType($item->mediaAsset),
            'state' => $position >= $duration ? 'Completed' : 'Running',
            'position' => $position,
            'duration' => $duration,
        ];
    }

    private function mediaFileName(string $path): string
    {
        return basename(str_replace('\\', '/', $path));
    }

    private function resolveVmixFilePath(string $path): string
    {
        $path = $this->mediaBrowser->resolveStoragePath($path);

        if (preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        $containerWorkspace = (string) config('balafon.media.container_workspace_path', base_path());
        $hostWorkspace = (string) config('balafon.media.host_workspace_path', '');

        if ($hostWorkspace !== '' && str_starts_with($path, $containerWorkspace)) {
            $translated = $hostWorkspace.substr($path, strlen($containerWorkspace));

            return str_replace('/', '\\', $translated);
        }

        return $path;
    }

    private function syncGraphicSlot(
        BroadcastRun $run,
        BroadcastRunItem $item,
        ?array $desired,
        ?array $current,
        int $overlay,
    ): ?array {
        $signature = $desired === null ? null : sha1(json_encode($desired));

        if ($current !== null && ($current['signature'] ?? null) === $signature) {
            return $current;
        }

        if ($current !== null) {
            $this->hideAndRemoveGraphic($run, $item, $current, $overlay);
        }

        if ($desired === null) {
            return null;
        }

        $snapshot = $this->prepareGraphicInput($run, $item, $desired, $overlay);
        $this->requireSuccessful(
            $this->sendLoggedCommand($run->connection, $run, $item, "OverlayInput{$overlay}In", [
                'Input' => $snapshot['key'] ?: $snapshot['number'],
            ]),
            "Unable to display vMix overlay {$overlay}.",
        );

        return [
            'signature' => $signature,
            'asset_uuid' => $desired['uuid'] ?? null,
            'input_key' => $snapshot['key'] ?? null,
            'input_number' => $snapshot['number'] ?? null,
        ];
    }

    private function prepareGraphicInput(
        BroadcastRun $run,
        BroadcastRunItem $item,
        array $graphic,
        int $overlay,
    ): array {
        $type = $graphic['asset_type'] ?? '';
        $sourcePath = match ($type) {
            'LOGO', 'ANNOUNCEMENT_VIDEO' => $this->resolveVmixGraphicPath((string) ($graphic['file_path'] ?? '')),
            'ANNOUNCEMENT_TEXT' => $this->tickerOverlayUrl($item),
            default => throw new RuntimeException('Unsupported graphic asset type.'),
        };

        if ($sourcePath === '') {
            throw new RuntimeException('The vMix graphic source is not configured.');
        }

        $inputType = match ($type) {
            'LOGO' => 'Image',
            'ANNOUNCEMENT_TEXT' => 'Browser',
            'ANNOUNCEMENT_VIDEO' => 'Video',
        };
        $knownInputKeys = $this->isMock()
            ? []
            : array_column($this->inputSnapshots($run->connection), 'key');

        $this->requireSuccessful(
            $this->sendLoggedCommand($run->connection, $run, $item, 'AddInput', [
                'Value' => sprintf('%s|%s', $inputType, $sourcePath),
            ]),
            'Unable to add the graphic input to vMix.',
        );

        $snapshot = $this->isMock()
            ? [
                'key' => sprintf('mock-graphic-%d-%d-%d', $run->id, $overlay, $item->id),
                'number' => (string) (100 + $overlay),
                'title' => $this->mediaFileName($sourcePath),
                'type' => $inputType,
                'state' => 'Running',
                'position' => 0,
                'duration' => 0,
            ]
            : $this->awaitAddedInputSnapshot(
                $run->connection,
                $knownInputKeys,
                $this->mediaFileName($sourcePath),
            );

        if ($snapshot === null) {
            throw new RuntimeException('vMix graphic input could not be resolved after AddInput.');
        }

        $input = $snapshot['key'] ?: $snapshot['number'];

        if ($type === 'LOGO') {
            $this->applyLogoLayout($run, $item, $input, $graphic);
        }

        if ($type === 'ANNOUNCEMENT_VIDEO') {
            if ((bool) ($graphic['loop_enabled'] ?? true)) {
                $this->requireSuccessful(
                    $this->sendLoggedCommand($run->connection, $run, $item, 'LoopOn', ['Input' => $input]),
                    'Unable to enable the vMix announcement loop.',
                );
            }

            $this->requireSuccessful(
                $this->sendLoggedCommand($run->connection, $run, $item, 'Restart', ['Input' => $input]),
                'Unable to restart the vMix announcement video.',
            );
            $this->requireSuccessful(
                $this->sendLoggedCommand($run->connection, $run, $item, 'Play', ['Input' => $input]),
                'Unable to play the vMix announcement video.',
            );
        }

        return $snapshot;
    }

    private function hideAndRemoveGraphic(
        BroadcastRun $run,
        BroadcastRunItem $item,
        array $state,
        int $overlay,
    ): void {
        $this->sendLoggedCommand($run->connection, $run, $item, "OverlayInput{$overlay}Out");
        $input = $state['input_key'] ?? $state['input_number'] ?? null;

        if ($input !== null) {
            $this->sendLoggedCommand($run->connection, $run, $item, 'RemoveInput', [
                'Input' => $input,
            ]);
        }
    }

    private function persistGraphicsState(BroadcastRun $run, array $state): void
    {
        $context = $run->context ?? [];
        $context['graphics_state'] = $state;
        $run->update(['context' => $context]);
    }

    private function resolveVmixGraphicPath(string $path): string
    {
        if ($path === '') {
            throw new RuntimeException('The selected graphic file is missing.');
        }

        $path = $this->mediaBrowser->resolveStoragePath($path);

        if (preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        $containerWorkspace = (string) config('balafon.media.container_workspace_path', base_path());
        $hostWorkspace = (string) config('balafon.media.host_workspace_path', '');

        if ($hostWorkspace !== '' && str_starts_with($path, $containerWorkspace)) {
            return str_replace('/', '\\', $hostWorkspace.substr($path, strlen($containerWorkspace)));
        }

        return $path;
    }

    private function requireSuccessful(array $response, string $message): void
    {
        if (($response['status'] ?? 'failed') !== 'success') {
            throw new RuntimeException($response['error_message'] ?? $message);
        }
    }

    private function inputSnapshots(VmixConnection $connection): array
    {
        $status = $this->provider->getStatus($connection)->toArray();
        $xml = $status['raw']['xml'] ?? null;

        if (! is_string($xml)) {
            return [];
        }

        $parsed = simplexml_load_string($xml);

        if ($parsed === false) {
            return [];
        }

        $inputs = [];

        foreach ($parsed->inputs->input ?? [] as $input) {
            $inputs[] = [
                'key' => (string) ($input['key'] ?? ''),
                'number' => (string) ($input['number'] ?? ''),
                'title' => (string) ($input['title'] ?? ''),
                'type' => (string) ($input['type'] ?? ''),
                'state' => (string) ($input['state'] ?? ''),
                'position' => (int) ($input['position'] ?? 0),
                'duration' => (int) ($input['duration'] ?? 0),
            ];
        }

        return $inputs;
    }

    private function awaitAddedInputSnapshot(
        VmixConnection $connection,
        array $knownInputKeys,
        string $expectedTitle,
    ): ?array {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $inputs = $this->inputSnapshots($connection);
            $newInput = collect($inputs)->last(
                fn (array $input): bool => ! in_array($input['key'], $knownInputKeys, true),
            );

            if ($newInput !== null) {
                return $newInput;
            }

            $titleMatch = collect($inputs)->last(
                fn (array $input): bool => $input['title'] === $expectedTitle,
            );

            if ($titleMatch !== null) {
                return $titleMatch;
            }

            usleep(250000);
        }

        return null;
    }

    private function tickerOverlayUrl(BroadcastRunItem $item): string
    {
        $baseUrl = rtrim((string) config('balafon.overlay_base_url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('The Balafon overlay base URL is not configured.');
        }

        return sprintf('%s/overlays/ticker/%s', $baseUrl, rawurlencode($item->uuid));
    }

    private function applyLogoLayout(
        BroadcastRun $run,
        BroadcastRunItem $item,
        string $input,
        array $graphic,
    ): void {
        $scale = min(50, max(10, (int) ($graphic['logo_scale'] ?? 20))) / 100;
        $offset = round(max(0, 0.94 - $scale), 2);
        [$panX, $panY] = match ($graphic['logo_position'] ?? 'TOP_RIGHT') {
            'TOP_LEFT' => [-$offset, $offset],
            'BOTTOM_RIGHT' => [$offset, -$offset],
            'BOTTOM_LEFT' => [-$offset, -$offset],
            default => [$offset, $offset],
        };

        foreach ([
            'SetZoom' => $scale,
            'SetPanX' => $panX,
            'SetPanY' => $panY,
        ] as $command => $value) {
            $this->requireSuccessful(
                $this->sendLoggedCommand($run->connection, $run, $item, $command, [
                    'Input' => $input,
                    'Value' => $value,
                ]),
                "Unable to apply {$command} to the vMix logo.",
            );
        }
    }
}
