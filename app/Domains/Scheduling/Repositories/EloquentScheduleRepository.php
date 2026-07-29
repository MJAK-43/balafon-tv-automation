<?php

namespace App\Domains\Scheduling\Repositories;

use App\Domains\Scheduling\Models\Schedule;
use App\Domains\Scheduling\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentScheduleRepository implements ScheduleRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage);
    }

    public function list(array $filters = []): Collection
    {
        return $this->query($filters)->get();
    }

    public function findByUuid(string $uuid): ?Schedule
    {
        return Schedule::query()
            ->with(['channel', 'playlist.items.mediaAsset'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $payload): Schedule
    {
        return Schedule::query()->create($payload);
    }

    public function update(Schedule $schedule, array $payload): Schedule
    {
        $schedule->update($payload);

        return $schedule->refresh()->load(['channel', 'playlist.items.mediaAsset']);
    }

    public function delete(Schedule $schedule): void
    {
        $schedule->delete();
    }

    private function query(array $filters = []): Builder
    {
        return Schedule::query()
            ->with(['channel', 'playlist.items.mediaAsset'])
            ->when($filters['channel_id'] ?? null, fn (Builder $query, int $channelId) => $query->where('channel_id', $channelId))
            ->when($filters['utc_from'] ?? null, fn (Builder $query, string $utcFrom) => $query->where('starts_at', '>=', $utcFrom))
            ->when($filters['utc_to'] ?? null, fn (Builder $query, string $utcTo) => $query->where('starts_at', '<', $utcTo))
            ->orderBy('starts_at');
    }
}
