<?php

namespace App\Http\Controllers\Api\V1\Scheduling;

use App\Domains\Scheduling\Services\SchedulePlannerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleConflictController extends Controller
{
    public function __construct(
        private readonly SchedulePlannerService $schedules,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'channel_id' => ['nullable', 'integer', 'exists:channels,id'],
            'date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'timezone' => ['nullable', 'timezone'],
        ]);

        return response()->json($this->schedules->conflicts($filters));
    }
}
