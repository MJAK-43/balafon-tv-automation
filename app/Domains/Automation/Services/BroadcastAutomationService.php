<?php

namespace App\Domains\Automation\Services;

use App\Domains\Automation\Repositories\Contracts\BroadcastRunRepositoryInterface;
use App\Domains\Audit\Services\AuditService;
use App\Domains\Scheduling\Models\Schedule;
use Illuminate\Support\Carbon;

class BroadcastAutomationService
{
    public function __construct(
        private readonly BroadcastRunRepositoryInterface $runs,
        private readonly PlaylistExecutionService $playlistExecution,
        private readonly ScheduleMonitorService $monitor,
        private readonly AuditService $audit,
    ) {
    }

    public function startDueSchedules(): int
    {
        $started = 0;

        $dueSchedules = Schedule::query()
            ->with('playlist.items.mediaAsset')
            ->where('status', 'SCHEDULED')
            ->where('starts_at', '<=', Carbon::now('UTC')->format('Y-m-d H:i:s'))
            ->orderBy('starts_at')
            ->get();

        foreach ($dueSchedules as $schedule) {
            if ($this->runs->findActiveByScheduleId($schedule->id) !== null) {
                continue;
            }

            try {
                $run = $this->playlistExecution->createRun($schedule);
                $this->playlistExecution->startRun($run);
                $started++;

                $this->audit->log('automation.broadcast_started', 'schedule', (string) $schedule->id, [
                    'broadcast_run_id' => $run->id,
                    'playlist_title' => $schedule->playlist->title,
                ]);
            } catch (\Throwable $exception) {
                $this->audit->log('automation.broadcast_failed_to_start', 'schedule', (string) $schedule->id, [
                    'error' => $exception->getMessage(),
                    'playlist_title' => $schedule->playlist->title,
                ]);
            }
        }

        return $started;
    }

    public function monitorActiveRuns(): void
    {
        $this->monitor->monitorActiveRuns();
    }

    public function controlCenter(): array
    {
        $currentRun = $this->runs->currentRun();
        $nextSchedule = Schedule::query()
            ->with('playlist')
            ->where('status', 'SCHEDULED')
            ->where('starts_at', '>', Carbon::now('UTC')->format('Y-m-d H:i:s'))
            ->orderBy('starts_at')
            ->first();

        return [
            'current_run' => $currentRun,
            'next_schedule' => $nextSchedule,
            'recent_runs' => $this->runs->latest(),
        ];
    }
}
