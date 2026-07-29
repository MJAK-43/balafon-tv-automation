<?php

namespace App\Domains\Automation\Repositories\Contracts;

use App\Domains\Automation\Models\BroadcastRun;
use Illuminate\Database\Eloquent\Collection;

interface BroadcastRunRepositoryInterface
{
    public function create(array $payload): BroadcastRun;

    public function update(BroadcastRun $run, array $payload): BroadcastRun;

    public function findByUuid(string $uuid): ?BroadcastRun;

    public function findActiveByScheduleId(int $scheduleId): ?BroadcastRun;

    public function activeRuns(): Collection;

    public function latest(int $limit = 10): Collection;

    public function currentRun(): ?BroadcastRun;
}
