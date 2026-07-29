<?php

namespace App\Jobs;

use App\Domains\Automation\Services\BroadcastAutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessScheduledBroadcastsJob implements ShouldQueue
{
    use Queueable;

    public function handle(BroadcastAutomationService $automation): void
    {
        $automation->startDueSchedules();
    }
}
