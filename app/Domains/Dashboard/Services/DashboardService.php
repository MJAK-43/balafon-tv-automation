<?php

namespace App\Domains\Dashboard\Services;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\System\Models\SystemDiagnostic;
use App\Domains\Vmix\Models\VmixCommandLog;
use App\Domains\Vmix\Services\VmixApiService;

class DashboardService
{
    public function __construct(
        private readonly VmixApiService $vmix,
    ) {
    }

    public function summary(): array
    {
        try {
            $vmix = $this->vmix->getStatus();
        } catch (\Throwable $exception) {
            $vmix = [
                'connected' => false,
                'version' => null,
                'edition' => null,
                'active_input' => null,
                'preview_input' => null,
                'streaming' => false,
                'recording' => false,
                'external' => false,
                'fullscreen' => false,
                'inputs_count' => 0,
                'inputs' => [],
                'raw' => [],
                'error' => $exception->getMessage(),
            ];
        }

        return [
            'vmix' => $vmix,
            'connections' => $this->vmix->listConnections(),
            'system' => SystemDiagnostic::query()->latest('checked_at')->first(),
            'latest_audits' => AuditLog::query()->latest('created_at')->limit(10)->get(),
            'latest_vmix_tests' => VmixCommandLog::query()->latest('executed_at')->limit(10)->get(),
        ];
    }

    public function vmixStatus(): array
    {
        return $this->vmix->getStatus();
    }

    public function systemStatus(): ?SystemDiagnostic
    {
        return SystemDiagnostic::query()->latest('checked_at')->first();
    }
}
