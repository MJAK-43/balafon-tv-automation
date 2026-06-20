<?php

namespace App\Http\Controllers\Api\V1\Vmix;

use App\Domains\Vmix\Services\VmixApiService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VmixController extends Controller
{
    public function __construct(
        private readonly VmixApiService $vmix,
    ) {
    }

    public function connections(): JsonResponse
    {
        return response()->json($this->vmix->listConnections());
    }

    public function updateConnection(string $uuid, Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'timeout_ms' => ['required', 'integer', 'min:100', 'max:30000'],
            'is_active' => ['required', 'boolean'],
        ]);

        return response()->json($this->vmix->updateConnection($uuid, $payload));
    }

    public function status(): JsonResponse
    {
        return response()->json($this->vmix->getStatus());
    }

    public function inputs(): JsonResponse
    {
        return response()->json($this->vmix->getInputs());
    }

    public function testConnection(string $uuid, Request $request): JsonResponse
    {
        return response()->json($this->vmix->testConnection($uuid, $request->user()?->id));
    }

    public function connectionStatus(string $uuid): JsonResponse
    {
        return response()->json($this->vmix->getStatus($uuid));
    }

    public function playTest(Request $request): JsonResponse
    {
        return response()->json($this->vmix->playTest($request->user()?->id));
    }
}
