<?php

namespace App\Domains\Vmix\Repositories;

use App\Domains\Vmix\Models\VmixConnection;
use App\Domains\Vmix\Repositories\Contracts\VmixConnectionRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EloquentVmixConnectionRepository implements VmixConnectionRepositoryInterface
{
    public function allActive(): Collection
    {
        return VmixConnection::query()->where('is_active', true)->get();
    }

    public function all(): Collection
    {
        return VmixConnection::query()->get();
    }

    public function findByUuid(string $uuid): ?VmixConnection
    {
        return VmixConnection::query()->where('uuid', $uuid)->first();
    }

    public function saveHealth(VmixConnection $connection, string $status): VmixConnection
    {
        $connection->forceFill([
            'health_status' => $status,
            'last_health_check_at' => Carbon::now(),
        ])->save();

        return $connection->refresh();
    }
}
