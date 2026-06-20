<?php

namespace App\Domains\System\Repositories;

use App\Domains\System\Models\License;
use App\Domains\System\Repositories\Contracts\LicenseRepositoryInterface;

class EloquentLicenseRepository implements LicenseRepositoryInterface
{
    public function first(): ?License
    {
        return License::query()->first();
    }
}
