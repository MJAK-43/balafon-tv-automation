<?php

namespace App\Domains\Automation\Services;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Automation\Enums\BroadcastState;
use App\Domains\Automation\Models\BroadcastRun;
use App\Domains\Automation\Models\BroadcastRunItem;
use App\Domains\Automation\Repositories\Contracts\BroadcastRunRepositoryInterface;
use App\Domains\Scheduling\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PlaylistExecutionService
{
    public function __construct(
        private readonly BroadcastRunRepositoryInterface $runs,
        private readonly BroadcastStateMachine $stateMachine,
        private readonly VmixExecutionService $vmix,
        private readonly AuditService $audit,
    ) {}

    public function createRun(Schedule $schedule): BroadcastRun
    {
        $schedule->loadMissing([
            'playlist.items.mediaAsset',
            'playlist.items.logo',
            'playlist.items.announcement',
        ]);
        $connection = $this->vmix->resolveConnection();

        return DB::transaction(function () use ($schedule, $connection): BroadcastRun {
            $run = $this->runs->create([
                'uuid' => (string) Str::uuid(),
                'schedule_id' => $schedule->id,
                'vmix_connection_id' => $connection->id,
                'state' => BroadcastState::PENDING->value,
                'context' => [
                    'playlist_title' => $schedule->playlist->title,
                    'channel_id' => $schedule->channel_id,
                ],
            ]);

            foreach ($schedule->playlist->items as $index => $playlistItem) {
                $run->items()->create([
                    'uuid' => (string) Str::uuid(),
                    'playlist_item_id' => $playlistItem->id,
                    'media_asset_id' => $playlistItem->media_asset_id,
                    'sequence' => $index + 1,
                    'state' => BroadcastState::PENDING->value,
                    'context' => [
                        'graphics' => [
                            'logo' => $this->snapshotGraphic($playlistItem->logo),
                            'announcement' => $this->snapshotGraphic($playlistItem->announcement),
                        ],
                    ],
                ]);
            }

            return $run->fresh(['items.mediaAsset', 'schedule.playlist', 'connection']);
        });
    }

    public function startRun(BroadcastRun $run): BroadcastRun
    {
        $firstItem = $run->items->sortBy('sequence')->first();

        if ($firstItem === null) {
            throw new RuntimeException('The broadcast run has no items.');
        }

        $run = $this->transitionRun($run, BroadcastState::PREPARING->value, [
            'started_at' => now(),
        ]);

        return $this->playItem($run, $firstItem);
    }

    public function playNext(BroadcastRun $run): BroadcastRun
    {
        $currentSequence = $run->current_sequence ?? 0;
        $nextItem = $this->nextItem($run, $currentSequence);

        if ($nextItem === null) {
            return $this->completeRun($run);
        }

        return $this->playItem($run, $nextItem);
    }

    public function preloadNext(BroadcastRun $run): ?BroadcastRunItem
    {
        $currentSequence = $run->current_sequence ?? 0;
        $nextItem = $this->nextItem($run, $currentSequence);

        if ($nextItem === null) {
            return null;
        }

        if (
            $nextItem->state === BroadcastState::PREPARING->value
            && ($nextItem->context['preloaded'] ?? false)
            && ($nextItem->vmix_input_key !== null || $nextItem->vmix_input_number !== null)
        ) {
            return $nextItem;
        }

        $nextItem->update(['state' => BroadcastState::PREPARING->value]);

        try {
            $snapshot = $this->vmix->prepareInput($run, $nextItem);
        } catch (\Throwable $exception) {
            $nextItem->update([
                'state' => BroadcastState::PENDING->value,
                'context' => array_merge($nextItem->context ?? [], [
                    'preload_error' => $exception->getMessage(),
                ]),
            ]);

            $this->audit->log('automation.item_preload_failed', 'broadcast_run', (string) $run->id, [
                'broadcast_run_item_id' => $nextItem->id,
                'sequence' => $nextItem->sequence,
                'media_title' => $nextItem->mediaAsset->title,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        $nextItem->update([
            'vmix_input_key' => $snapshot['key'] ?? null,
            'vmix_input_number' => $snapshot['number'] ?? null,
            'last_known_position_ms' => $snapshot['position'] ?? 0,
            'last_known_duration_ms' => $snapshot['duration'] ?? (($nextItem->mediaAsset->duration_seconds ?? 0) * 1000),
            'context' => array_filter(array_merge($nextItem->context ?? [], [
                'vmix_title' => $snapshot['title'] ?? null,
                'vmix_type' => $snapshot['type'] ?? null,
                'preloaded' => true,
                'preloaded_at' => now()->toIso8601String(),
                'preload_error' => null,
            ]), fn (mixed $value): bool => $value !== null),
        ]);

        $this->audit->log('automation.item_preloaded', 'broadcast_run', (string) $run->id, [
            'broadcast_run_item_id' => $nextItem->id,
            'sequence' => $nextItem->sequence,
            'media_title' => $nextItem->mediaAsset->title,
            'vmix_input_number' => $snapshot['number'] ?? null,
        ]);

        return $nextItem->refresh();
    }

    public function advanceFromCurrentItem(BroadcastRun $run, BroadcastRunItem $item): BroadcastRun
    {
        $nextItem = $this->nextItem($run, $item->sequence);

        if ($nextItem === null) {
            // Remove overlays while Program still owns the final media input.
            $this->vmix->clearGraphics($run, $item);
            $run = $this->completeCurrentItem($run, $item);

            return $this->completeRun($run);
        }

        // Switch to the prepared input before removing the item currently on Program.
        $run = $this->playItem($run, $nextItem);

        return $this->completeCurrentItem($run, $item);
    }

    public function completeCurrentItem(BroadcastRun $run, BroadcastRunItem $item): BroadcastRun
    {
        $item->update([
            'state' => BroadcastState::COMPLETED->value,
            'completed_at' => now(),
        ]);

        $this->vmix->removeInput($run, $item);

        $this->audit->log('automation.item_completed', 'broadcast_run', (string) $run->id, [
            'broadcast_run_item_id' => $item->id,
            'sequence' => $item->sequence,
            'media_title' => $item->mediaAsset->title,
        ]);

        return $run->refresh()->load(['items.mediaAsset', 'schedule.playlist', 'connection', 'currentMediaAsset']);
    }

    public function failCurrentItem(BroadcastRun $run, BroadcastRunItem $item, string $message): BroadcastRun
    {
        $this->vmix->clearGraphics($run, $item);

        $item->update([
            'state' => BroadcastState::FAILED->value,
            'failed_at' => now(),
            'error_message' => $message,
        ]);

        $run = $this->transitionRun($run, BroadcastState::FAILED->value, [
            'last_error' => $message,
            'failed_at' => now(),
        ]);
        $run->schedule->update(['status' => 'FAILED']);

        $this->audit->log('automation.item_failed', 'broadcast_run', (string) $run->id, [
            'broadcast_run_item_id' => $item->id,
            'sequence' => $item->sequence,
            'media_title' => $item->mediaAsset->title,
            'error' => $message,
        ]);

        return $run;
    }

    private function playItem(BroadcastRun $run, BroadcastRunItem $item): BroadcastRun
    {
        $preloaded = ($item->context['preloaded'] ?? false)
            && ($item->vmix_input_key !== null || $item->vmix_input_number !== null);

        $item->update([
            'state' => BroadcastState::PREPARING->value,
        ]);

        try {
            if ($preloaded) {
                $snapshot = [
                    'key' => $item->vmix_input_key,
                    'number' => $item->vmix_input_number,
                    'title' => $item->context['vmix_title'] ?? null,
                    'type' => $item->context['vmix_type'] ?? null,
                    'position' => 0,
                    'duration' => $item->last_known_duration_ms,
                ];
                $this->vmix->playPreparedInput($run, $item, $snapshot);
            } else {
                $snapshot = $this->vmix->prepareAndPlay($run, $item);
            }
        } catch (\Throwable $exception) {
            $item->update([
                'state' => BroadcastState::FAILED->value,
                'failed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            $this->transitionRun($run, BroadcastState::FAILED->value, [
                'last_error' => $exception->getMessage(),
                'failed_at' => now(),
            ]);
            $run->schedule->update(['status' => 'FAILED']);

            $this->audit->log('automation.item_failed', 'broadcast_run', (string) $run->id, [
                'broadcast_run_item_id' => $item->id,
                'sequence' => $item->sequence,
                'media_title' => $item->mediaAsset->title,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $item->update([
            'state' => BroadcastState::ON_AIR->value,
            'started_at' => now(),
            'vmix_input_key' => $snapshot['key'] ?? null,
            'vmix_input_number' => $snapshot['number'] ?? null,
            'last_known_position_ms' => $snapshot['position'] ?? 0,
            'last_known_duration_ms' => $snapshot['duration'] ?? (($item->mediaAsset->duration_seconds ?? 0) * 1000),
            'context' => array_filter(array_merge($item->context ?? [], [
                'vmix_title' => $snapshot['title'] ?? null,
                'vmix_type' => $snapshot['type'] ?? null,
                'preloaded' => false,
                'resume_attempts' => 0,
            ]), fn (mixed $value): bool => $value !== null),
        ]);

        $run = $this->transitionRun($run, BroadcastState::ON_AIR->value, [
            'current_playlist_item_id' => $item->playlist_item_id,
            'current_media_asset_id' => $item->media_asset_id,
            'current_sequence' => $item->sequence,
        ]);
        $run->schedule->update(['status' => 'ON_AIR']);

        try {
            $this->vmix->syncGraphics($run, $item->refresh());
        } catch (\Throwable $exception) {
            $this->audit->log('automation.graphics_failed', 'broadcast_run', (string) $run->id, [
                'broadcast_run_item_id' => $item->id,
                'sequence' => $item->sequence,
                'media_title' => $item->mediaAsset->title,
                'error' => $exception->getMessage(),
            ]);
        }

        $this->audit->log('automation.item_started', 'broadcast_run', (string) $run->id, [
            'broadcast_run_item_id' => $item->id,
            'sequence' => $item->sequence,
            'media_title' => $item->mediaAsset->title,
            'vmix_input_number' => $snapshot['number'] ?? null,
        ]);

        return $run;
    }

    private function completeRun(BroadcastRun $run): BroadcastRun
    {
        $currentItem = $run->items->firstWhere('sequence', $run->current_sequence);
        if ($currentItem !== null) {
            $this->vmix->clearGraphics($run, $currentItem);
        }

        $run = $this->transitionRun($run, BroadcastState::COMPLETED->value, [
            'completed_at' => now(),
        ]);
        $run->schedule->update(['status' => 'COMPLETED']);

        $this->audit->log('automation.broadcast_completed', 'broadcast_run', (string) $run->id, [
            'schedule_id' => $run->schedule_id,
        ]);

        return $run;
    }

    private function nextItem(BroadcastRun $run, int $currentSequence): ?BroadcastRunItem
    {
        return $run->items()
            ->with('mediaAsset')
            ->where('sequence', '>', $currentSequence)
            ->orderBy('sequence')
            ->first();
    }

    private function transitionRun(BroadcastRun $run, string $to, array $attributes = []): BroadcastRun
    {
        $current = $run->state;

        if ($current !== $to) {
            $this->stateMachine->assertTransition($current, $to);
        }

        $attributes['state'] = $to;

        return $this->runs->update($run, $attributes);
    }

    private function snapshotGraphic(mixed $asset): ?array
    {
        if ($asset === null) {
            return null;
        }

        return [
            'id' => $asset->id,
            'uuid' => $asset->uuid,
            'name' => $asset->name,
            'asset_type' => $asset->asset_type,
            'file_path' => $asset->rawFilePath(),
            'text_content' => $asset->text_content,
            'logo_position' => $asset->logo_position,
            'logo_scale' => (int) $asset->logo_scale,
            'text_color' => $asset->text_color,
            'text_background_color' => $asset->text_background_color,
            'text_font' => $asset->text_font,
            'ticker_speed' => (int) $asset->ticker_speed,
            'loop_enabled' => (bool) $asset->loop_enabled,
        ];
    }
}
