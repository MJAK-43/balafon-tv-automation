<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Domains\Dashboard\Services\DashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {
    }

    public function summary(): JsonResponse
    {
        return response()->json($this->dashboard->summary());
    }

    public function vmixStatus(): JsonResponse
    {
        return response()->json($this->dashboard->vmixStatus());
    }

    public function systemStatus(): JsonResponse
    {
        return response()->json($this->dashboard->systemStatus());
    }
}
