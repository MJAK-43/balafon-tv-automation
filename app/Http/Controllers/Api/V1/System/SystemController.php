<?php

namespace App\Http\Controllers\Api\V1\System;

use App\Domains\System\Services\SystemCheckService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    public function __construct(
        private readonly SystemCheckService $system,
    ) {
    }

    public function check(): JsonResponse
    {
        return response()->json($this->system->health());
    }

    public function requirements(): JsonResponse
    {
        return response()->json($this->system->requirements());
    }

    public function health(): JsonResponse
    {
        return response()->json($this->system->health());
    }

    public function vmix(): JsonResponse
    {
        return response()->json($this->system->health()['vmix']);
    }

    public function runDiagnostic(): JsonResponse
    {
        return response()->json($this->system->runFullDiagnostic());
    }
}
