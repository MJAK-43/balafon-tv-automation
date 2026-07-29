<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Scheduling\Models\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleConflictService
{
    public function detect(Collection $schedules): array
    {
        $conflicts = [];
        $sorted = $schedules->sortBy('starts_at')->values();

        for ($index = 0; $index < $sorted->count(); $index++) {
            /** @var Schedule $current */
            $current = $sorted[$index];
            $currentEnd = $this->resolveEnd($current);

            if ($currentEnd === null) {
                $conflicts[] = [
                    'type' => 'invalid_duration',
                    'schedule_uuid' => $current->uuid,
                    'related_schedule_uuid' => null,
                    'message' => 'The playlist duration is missing, so the broadcast window cannot be validated.',
                ];

                continue;
            }

            for ($nextIndex = $index + 1; $nextIndex < $sorted->count(); $nextIndex++) {
                /** @var Schedule $next */
                $next = $sorted[$nextIndex];
                $nextStart = Carbon::parse($next->starts_at);

                if ($nextStart->greaterThanOrEqualTo($currentEnd)) {
                    break;
                }

                $conflicts[] = [
                    'type' => 'overlap',
                    'schedule_uuid' => $current->uuid,
                    'related_schedule_uuid' => $next->uuid,
                    'message' => 'Two playlists overlap on the same channel.',
                ];
            }
        }

        return $conflicts;
    }

    public function resolveEnd(Schedule $schedule): ?Carbon
    {
        if ($schedule->ends_at !== null) {
            return Carbon::parse($schedule->ends_at);
        }

        $duration = (int) ($schedule->playlist?->items->sum(fn ($item) => $item->mediaAsset?->duration_seconds ?? 0) ?? 0);

        if ($duration <= 0) {
            return null;
        }

        return Carbon::parse($schedule->starts_at)->copy()->addSeconds($duration);
    }
}
