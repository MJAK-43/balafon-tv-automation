<?php

namespace App\Http\Controllers\Api\V1\System;

use App\Domains\System\Services\SystemCheckService;
use App\Domains\System\Services\SystemSettingService;
use App\Http\Controllers\Controller;
use RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function __construct(
        private readonly SystemCheckService $system,
        private readonly SystemSettingService $settings,
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

    public function mediaRoot(): JsonResponse
    {
        return response()->json([
            'media_root' => $this->settings->getMediaRoot(),
        ]);
    }

    public function updateMediaRoot(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'media_root' => ['required', 'string', 'max:2048'],
        ]);

        try {
            $mediaRoot = $this->settings->setMediaRoot($payload['media_root']);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'media_root' => $mediaRoot,
        ]);
    }
}
