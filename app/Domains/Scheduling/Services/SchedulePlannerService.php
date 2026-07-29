<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Channel\Models\Channel;
use App\Domains\Playlist\Repositories\Contracts\PlaylistRepositoryInterface;
use App\Domains\Scheduling\Models\Schedule;
use App\Domains\Scheduling\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchedulePlannerService
{
    public function __construct(
        private readonly ScheduleRepositoryInterface $schedules,
        private readonly PlaylistRepositoryInterface $playlists,
        private readonly ScheduleConflictService $conflicts,
    ) {
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->schedules->paginate($this->normalizeFilters($filters), $perPage);
    }

    public function list(array $filters = []): Collection
    {
        return $this->schedules->list($this->normalizeFilters($filters));
    }

    public function findByUuid(string $uuid): ?Schedule
    {
        return $this->schedules->findByUuid($uuid);
    }

    public function create(array $payload, ?int $actorId = null): Schedule
    {
        Channel::query()->findOrFail($payload['channel_id']);
        $timezone = $payload['timezone'] ?? config('app.timezone');

        $payload['uuid'] = (string) Str::uuid();
        $payload['created_by'] = $actorId;
        $payload['updated_by'] = $actorId;
        $payload['starts_at'] = $this->toUtc($payload['starts_at'], $timezone);
        $payload['ends_at'] = $this->resolveEndsAt($payload['playlist_id'], $payload['starts_at'], $payload['ends_at'] ?? null);
        unset($payload['timezone']);

        return $this->schedules->create($payload)->load(['channel', 'playlist.items.mediaAsset']);
    }

    public function update(Schedule $schedule, array $payload, ?int $actorId = null): Schedule
    {
        Channel::query()->findOrFail($payload['channel_id'] ?? $schedule->channel_id);
        $timezone = $payload['timezone'] ?? config('app.timezone');

        $payload['updated_by'] = $actorId;
        if (array_key_exists('starts_at', $payload)) {
            $payload['starts_at'] = $this->toUtc($payload['starts_at'], $timezone);
        }
        if (array_key_exists('ends_at', $payload) && $payload['ends_at'] !== null) {
            $payload['ends_at'] = $this->toUtc($payload['ends_at'], $timezone);
        }
        $payload['ends_at'] = $this->resolveEndsAt(
            $payload['playlist_id'] ?? $schedule->playlist_id,
            $payload['starts_at'] ?? $schedule->starts_at,
            $payload['ends_at'] ?? null,
        );
        unset($payload['timezone']);

        return $this->schedules->update($schedule, $payload);
    }

    public function delete(Schedule $schedule): void
    {
        $this->schedules->delete($schedule);
    }

    public function duplicateDay(int $channelId, string $sourceDate, string $targetDate, ?string $timezone = null, ?int $actorId = null): array
    {
        $sourceSchedules = $this->schedules->list([
            'channel_id' => $channelId,
            'date' => $sourceDate,
            'timezone' => $timezone,
        ]);

        $planningTimezone = $timezone ?? config('app.timezone');
        $targetStart = Carbon::parse($targetDate, $planningTimezone)->startOfDay();
        $sourceStart = Carbon::parse($sourceDate, $planningTimezone)->startOfDay();

        return DB::transaction(function () use ($sourceSchedules, $targetStart, $sourceStart, $actorId, $planningTimezone): array {
            $duplicated = [];

            foreach ($sourceSchedules as $schedule) {
                $scheduleStartsAt = $schedule->starts_at->copy()->setTimezone($planningTimezone);
                $offsetSeconds = $scheduleStartsAt->getTimestamp() - $sourceStart->getTimestamp();
                $newStartsAt = $targetStart->copy()->addSeconds($offsetSeconds);

                $duplicated[] = $this->create([
                    'channel_id' => $schedule->channel_id,
                    'playlist_id' => $schedule->playlist_id,
                    'starts_at' => $newStartsAt->toDateTimeString(),
                    'ends_at' => null,
                    'status' => 'DRAFT',
                    'timezone' => $planningTimezone,
                ], $actorId);
            }

            return $duplicated;
        });
    }

    public function duplicateWeek(int $channelId, string $sourceWeekStart, string $targetWeekStart, ?string $timezone = null, ?int $actorId = null): array
    {
        $planningTimezone = $timezone ?? config('app.timezone');
        $sourceStart = Carbon::parse($sourceWeekStart, $planningTimezone)->startOfDay();
        $sourceEnd = $sourceStart->copy()->addDays(6)->endOfDay();
        $targetStart = Carbon::parse($targetWeekStart, $planningTimezone)->startOfDay();

        $sourceSchedules = $this->schedules->list([
            'channel_id' => $channelId,
            'date_from' => $sourceStart->toDateString(),
            'date_to' => $sourceEnd->toDateString(),
            'timezone' => $planningTimezone,
        ]);

        return DB::transaction(function () use ($sourceSchedules, $sourceStart, $targetStart, $actorId, $planningTimezone): array {
            $duplicated = [];

            foreach ($sourceSchedules as $schedule) {
                $scheduleStartsAt = $schedule->starts_at->copy()->setTimezone($planningTimezone);
                $offsetSeconds = $scheduleStartsAt->getTimestamp() - $sourceStart->getTimestamp();
                $newStartsAt = $targetStart->copy()->addSeconds($offsetSeconds);

                $duplicated[] = $this->create([
                    'channel_id' => $schedule->channel_id,
                    'playlist_id' => $schedule->playlist_id,
                    'starts_at' => $newStartsAt->toDateTimeString(),
                    'ends_at' => null,
                    'status' => 'DRAFT',
                    'timezone' => $planningTimezone,
                ], $actorId);
            }

            return $duplicated;
        });
    }

    public function conflicts(array $filters = []): array
    {
        $schedules = $this->schedules->list($filters);
        $grouped = $schedules->groupBy('channel_id');

        return $grouped->flatMap(function (Collection $channelSchedules): array {
            return $this->conflicts->detect($channelSchedules);
        })->values()->all();
    }

    private function resolveEndsAt(int $playlistId, string|\Carbon\CarbonInterface $startsAt, ?string $endsAt): ?string
    {
        if ($endsAt !== null) {
            return Carbon::parse($endsAt)->utc()->format('Y-m-d H:i:s');
        }

        $playlist = $this->playlists->findById($playlistId);
        $duration = (int) ($playlist?->items->sum(fn ($item) => $item->mediaAsset?->duration_seconds ?? 0) ?? 0);

        if ($duration <= 0) {
            return null;
        }

        $startsAtUtc = $startsAt instanceof \Carbon\CarbonInterface
            ? $startsAt->copy()->setTimezone('UTC')
            : Carbon::parse($startsAt, 'UTC');

        return $startsAtUtc->addSeconds($duration)->format('Y-m-d H:i:s');
    }

    private function normalizeFilters(array $filters): array
    {
        if (! isset($filters['channel_id'])) {
            return $filters;
        }

        $planningTimezone = $filters['timezone'] ?? config('app.timezone');

        if (isset($filters['date'])) {
            $start = Carbon::parse($filters['date'], $planningTimezone)->startOfDay()->utc();
            $end = $start->copy()->addDay();

            $filters['utc_from'] = $start->format('Y-m-d H:i:s');
            $filters['utc_to'] = $end->format('Y-m-d H:i:s');
            unset($filters['date']);
        }

        if (isset($filters['date_from'])) {
            $filters['utc_from'] = Carbon::parse($filters['date_from'], $planningTimezone)->startOfDay()->utc()->format('Y-m-d H:i:s');
            unset($filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $filters['utc_to'] = Carbon::parse($filters['date_to'], $planningTimezone)->endOfDay()->addSecond()->utc()->format('Y-m-d H:i:s');
            unset($filters['date_to']);
        }

        unset($filters['timezone']);

        return $filters;
    }

    private function toUtc(string|\Carbon\CarbonInterface $value, string $timezone): string
    {
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->copy()->setTimezone('UTC')->format('Y-m-d H:i:s');
        }

        return Carbon::parse($value, $timezone)->utc()->format('Y-m-d H:i:s');
    }
}
