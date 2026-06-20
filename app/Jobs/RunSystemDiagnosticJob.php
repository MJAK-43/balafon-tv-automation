<?php

namespace App\Jobs;

use App\Domains\System\Services\SystemCheckService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunSystemDiagnosticJob implements ShouldQueue
{
    use Queueable;

    public function handle(SystemCheckService $systemCheckService): void
    {
        $systemCheckService->runFullDiagnostic();
    }
}
