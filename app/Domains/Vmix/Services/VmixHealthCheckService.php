<?php

namespace App\Domains\Vmix\Services;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Vmix\Providers\Contracts\VmixProviderInterface;
use App\Domains\Vmix\Repositories\Contracts\VmixConnectionRepositoryInterface;

class VmixHealthCheckService
{
    public function __construct(
        private readonly VmixConnectionRepositoryInterface $connections,
        private readonly VmixProviderInterface $provider,
        private readonly AuditService $audit,
    ) {
    }

    public function checkAllActiveConnections(): array
    {
        return $this->connections->allActive()->map(function ($connection): array {
            try {
                $ok = $this->provider->healthCheck($connection);
                $saved = $this->connections->saveHealth($connection, $ok ? 'healthy' : 'offline');

                $payload = [
                    'connection' => $saved->name,
                    'health_status' => $saved->health_status,
                ];

                $this->audit->log('vmix.health_check', 'vmix_connection', (string) $saved->id, $payload);

                return $payload;
            } catch (\Throwable $exception) {
                $saved = $this->connections->saveHealth($connection, 'offline');

                $payload = [
                    'connection' => $saved->name,
                    'health_status' => $saved->health_status,
                    'error' => $exception->getMessage(),
                ];

                $this->audit->log('vmix.health_check_failed', 'vmix_connection', (string) $saved->id, $payload);

                return $payload;
            }
        })->all();
    }
}
