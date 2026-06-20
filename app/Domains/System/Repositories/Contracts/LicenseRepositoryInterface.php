<?php

namespace App\Domains\System\Repositories\Contracts;

use App\Domains\System\Models\License;

interface LicenseRepositoryInterface
{
    public function first(): ?License;
}
