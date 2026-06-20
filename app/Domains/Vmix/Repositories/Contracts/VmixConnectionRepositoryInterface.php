<?php

namespace App\Domains\Vmix\Repositories\Contracts;

use App\Domains\Vmix\Models\VmixConnection;
use Illuminate\Support\Collection;

interface VmixConnectionRepositoryInterface
{
    public function allActive(): Collection;

    public function all(): Collection;

    public function findByUuid(string $uuid): ?VmixConnection;

    public function saveHealth(VmixConnection $connection, string $status): VmixConnection;
}
