<?php

namespace App\Domains\Automation\Repositories;

use App\Domains\Automation\Enums\BroadcastState;
use App\Domains\Automation\Models\BroadcastRun;
use App\Domains\Automation\Repositories\Contracts\BroadcastRunRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentBroadcastRunRepository implements BroadcastRunRepositoryInterface
{
    public function create(array $payload): BroadcastRun
    {
        return BroadcastRun::query()->create($payload);
    }

    public function update(BroadcastRun $run, array $payload): BroadcastRun
    {
        $run->update($payload);

        return $run->refresh()->load(['items.mediaAsset', 'schedule.playlist', 'connection']);
    }

    public function findByUuid(string $uuid): ?BroadcastRun
    {
        return BroadcastRun::query()
            ->with(['items.mediaAsset', 'schedule.playlist', 'connection', 'currentMediaAsset'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function findActiveByScheduleId(int $scheduleId): ?BroadcastRun
    {
        return BroadcastRun::query()
            ->with(['items.mediaAsset', 'schedule.playlist', 'connection', 'currentMediaAsset'])
            ->where('schedule_id', $scheduleId)
            ->whereIn('state', [
                BroadcastState::PENDING->value,
                BroadcastState::PREPARING->value,
                BroadcastState::ON_AIR->value,
            ])
            ->first();
    }

    public function activeRuns(): Collection
    {
        return BroadcastRun::query()
            ->with(['items.mediaAsset', 'schedule.channel', 'schedule.playlist', 'connection', 'currentMediaAsset'])
            ->whereIn('state', [
                BroadcastState::PENDING->value,
                BroadcastState::PREPARING->value,
                BroadcastState::ON_AIR->value,
            ])
            ->orderBy('created_at')
            ->get();
    }

    public function latest(int $limit = 10): Collection
    {
        return BroadcastRun::query()
            ->with(['schedule.channel', 'schedule.playlist', 'currentMediaAsset'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function currentRun(): ?BroadcastRun
    {
        return BroadcastRun::query()
            ->with(['items.mediaAsset', 'schedule.channel', 'schedule.playlist', 'connection', 'currentMediaAsset'])
            ->whereIn('state', [
                BroadcastState::PREPARING->value,
                BroadcastState::ON_AIR->value,
            ])
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();
    }
}
