<?php

namespace App\Domains\Scheduling\Repositories\Contracts;

use App\Domains\Scheduling\Models\Schedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ScheduleRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function list(array $filters = []): Collection;

    public function findByUuid(string $uuid): ?Schedule;

    public function create(array $payload): Schedule;

    public function update(Schedule $schedule, array $payload): Schedule;

    public function delete(Schedule $schedule): void;
}
