<?php

namespace App\Jobs;

use App\Domains\Vmix\Services\VmixHealthCheckService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckVmixHealthJob implements ShouldQueue
{
    use Queueable;

    public function handle(VmixHealthCheckService $healthChecks): void
    {
        $healthChecks->checkAllActiveConnections();
    }
}
