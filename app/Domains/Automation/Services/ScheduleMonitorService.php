<?php

namespace App\Domains\Automation\Services;

use App\Domains\Automation\Enums\BroadcastState;
use App\Domains\Automation\Models\BroadcastRun;
use App\Domains\Automation\Models\BroadcastRunItem;
use App\Domains\Automation\Repositories\Contracts\BroadcastRunRepositoryInterface;
use App\Domains\Audit\Services\AuditService;
use Illuminate\Support\Carbon;

class ScheduleMonitorService
{
    public function __construct(
        private readonly BroadcastRunRepositoryInterface $runs,
        private readonly PlaylistExecutionService $playlistExecution,
        private readonly VmixExecutionService $vmix,
        private readonly AuditService $audit,
    ) {
    }

    public function monitorActiveRuns(): void
    {
        foreach ($this->runs->activeRuns() as $run) {
            $this->monitorRun($run);
        }
    }

    public function monitorRun(BroadcastRun $run): void
    {
        /** @var BroadcastRunItem|null $item */
        $item = $run->items->firstWhere('sequence', $run->current_sequence);

        if ($item === null) {
            return;
        }

        $snapshot = $this->vmix->monitor($run, $item);

        $item->update([
            'last_known_position_ms' => $snapshot['position'] ?? 0,
            'last_known_duration_ms' => $snapshot['duration'] ?? $item->last_known_duration_ms,
            'context' => array_merge($item->context ?? [], [
                'last_state' => $snapshot['state'] ?? 'Unknown',
                'last_polled_at' => now()->toIso8601String(),
            ]),
        ]);

        $remaining = max(0, (int) (($snapshot['duration'] ?? 0) - ($snapshot['position'] ?? 0)));

        if (($snapshot['state'] ?? null) === 'Completed') {
            $this->playlistExecution->advanceFromCurrentItem($run, $item);

            return;
        }

        if (($snapshot['state'] ?? null) === 'Running') {
            $this->audit->log('automation.item_progress', 'broadcast_run', (string) $run->id, [
                'broadcast_run_item_id' => $item->id,
                'position_ms' => $snapshot['position'] ?? 0,
                'remaining_ms' => $remaining,
                'media_title' => $item->mediaAsset->title,
            ]);

            $prepareThreshold = max(0, (int) config('balafon.automation.prepare_threshold_ms', 2000));

            if (($snapshot['duration'] ?? 0) > 0 && $remaining <= $prepareThreshold) {
                $preloadStartedAt = hrtime(true);
                $preloadedItem = $this->playlistExecution->preloadNext($run);
                $preloadElapsedMs = (int) round((hrtime(true) - $preloadStartedAt) / 1_000_000);

                $transitionLead = $preloadedItem === null
                    ? 0
                    : max(0, (int) config('balafon.automation.transition_lead_ms', 250));
                $waitMs = max(0, $remaining - $transitionLead - $preloadElapsedMs);

                if ($waitMs > 0) {
                    usleep($waitMs * 1000);
                }

                $this->playlistExecution->advanceFromCurrentItem($run, $item->refresh());
            }

            return;
        }

        if (($snapshot['state'] ?? null) === 'Paused' && ($snapshot['position'] ?? 0) < ($snapshot['duration'] ?? PHP_INT_MAX)) {
            $resumeAttempts = (int) (($item->context['resume_attempts'] ?? 0));

            if ($resumeAttempts < 1) {
                $item->update([
                    'context' => array_merge($item->context ?? [], [
                        'resume_attempts' => $resumeAttempts + 1,
                        'resume_requested_at' => now()->toIso8601String(),
                    ]),
                ]);

                $this->vmix->resumePlayback($run, $item);

                $this->audit->log('automation.item_resume_requested', 'broadcast_run', (string) $run->id, [
                    'broadcast_run_item_id' => $item->id,
                    'media_title' => $item->mediaAsset->title,
                ]);

                return;
            }
        }

        $startedAt = $item->started_at ?? $run->started_at ?? now();
        $expectedDuration = (int) (($snapshot['duration'] ?? $item->last_known_duration_ms ?? 0));
        $timeoutMs = $expectedDuration + config('balafon.automation.stalled_grace_ms', 4000);
        $elapsedMs = Carbon::parse($startedAt)->diffInMilliseconds(now());

        if (($snapshot['state'] ?? null) === 'Missing' || $elapsedMs > $timeoutMs) {
            $this->playlistExecution->failCurrentItem(
                $run,
                $item,
                sprintf('Playback monitoring failed for media "%s".', $item->mediaAsset->title)
            );
        }
    }
}
