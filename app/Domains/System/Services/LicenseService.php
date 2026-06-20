<?php

namespace App\Domains\System\Services;

use App\Domains\System\Models\License;
use App\Domains\System\Repositories\Contracts\LicenseRepositoryInterface;

class LicenseService
{
    public function __construct(
        private readonly LicenseRepositoryInterface $licenses,
    ) {
    }

    public function current(): ?License
    {
        return $this->licenses->first();
    }
}
