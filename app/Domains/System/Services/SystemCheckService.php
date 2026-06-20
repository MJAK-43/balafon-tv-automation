<?php

namespace App\Domains\System\Services;

use App\Domains\Audit\Services\AuditService;
use App\Domains\System\Models\SystemDiagnostic;
use Illuminate\Support\Str;

class SystemCheckService
{
    public function __construct(
        private readonly DiskHealthService $disk,
        private readonly MemoryHealthService $memory,
        private readonly CpuHealthService $cpu,
        private readonly ApplicationHealthService $application,
        private readonly EnvironmentValidatorService $environment,
        private readonly VmixDetectorService $vmixDetector,
        private readonly AuditService $audit,
    ) {
    }

    public function requirements(): array
    {
        return $this->environment->requirements();
    }

    public function health(): array
    {
        return [
            'os' => [
                'name' => php_uname('s'),
                'version' => php_uname('r'),
            ],
            'resources' => [
                'disk' => $this->disk->check(base_path()),
                'memory' => $this->memory->check(),
                'cpu' => $this->cpu->check(),
            ],
            'application' => $this->application->check(),
            'vmix' => $this->vmixDetector->detect(),
        ];
    }

    public function runFullDiagnostic(): array
    {
        $health = $this->health();
        $vmix = $health['vmix'][0] ?? [];

        $diagnostic = SystemDiagnostic::create([
            'uuid' => (string) Str::uuid(),
            'machine_name' => gethostname() ?: null,
            'os_name' => $health['os']['name'],
            'os_version' => $health['os']['version'],
            'vmix_detected' => (bool) ($vmix['reachable'] ?? false),
            'vmix_version' => $vmix['version'] ?? null,
            'vmix_api_reachable' => (bool) ($vmix['reachable'] ?? false),
            'tested_endpoints' => $health['vmix'],
            'local_ip' => gethostbyname(gethostname()),
            'total_disk_bytes' => $health['resources']['disk']['total_bytes'],
            'free_disk_bytes' => $health['resources']['disk']['free_bytes'],
            'total_memory_bytes' => $health['resources']['memory']['total_bytes'],
            'available_memory_bytes' => $health['resources']['memory']['available_bytes'],
            'cpu_cores' => $health['resources']['cpu']['cores'],
            'cpu_load_percent' => $health['resources']['cpu']['load_percent'],
            'postgres_ok' => $health['application']['postgres_ok'],
            'redis_ok' => $health['application']['redis_ok'],
            'scheduler_ok' => $health['application']['scheduler_ok'],
            'queue_workers_ok' => $health['application']['queue_workers_ok'],
            'context' => $health,
            'checked_at' => now(),
        ]);

        $payload = array_merge(['success' => true], $health, ['diagnostic_id' => $diagnostic->uuid]);

        $this->audit->log('system.diagnostic_run', 'system_diagnostic', (string) $diagnostic->id, $payload);

        return $payload;
    }
}
