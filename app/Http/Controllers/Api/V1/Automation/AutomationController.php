<?php

namespace App\Http\Controllers\Api\V1\Automation;

use App\Domains\Automation\Services\BroadcastAutomationService;
use App\Domains\Automation\Services\VmixExecutionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AutomationController extends Controller
{
    public function __construct(
        private readonly BroadcastAutomationService $automation,
        private readonly VmixExecutionService $vmix,
    ) {
    }

    public function controlCenter(): JsonResponse
    {
        return response()->json(array_merge(
            $this->automation->controlCenter(),
            ['vmix' => $this->vmix->status()],
        ));
    }

    public function tick(): JsonResponse
    {
        $started = $this->automation->startDueSchedules();
        $this->automation->monitorActiveRuns();

        return response()->json([
            'started_runs' => $started,
            'message' => 'Automation tick completed.',
        ]);
    }
}
