<?php

namespace App\Domains\System\Services;

use App\Domains\Vmix\Services\VmixApiService;
use App\Domains\Vmix\Repositories\Contracts\VmixConnectionRepositoryInterface;

class VmixDetectorService
{
    public function __construct(
        private readonly VmixConnectionRepositoryInterface $connections,
        private readonly VmixApiService $vmix,
    ) {
    }

    public function detect(): array
    {
        $results = [];

        foreach ($this->connections->all() as $connection) {
            try {
                $status = $this->vmix->getStatus($connection->uuid);

                $results[] = [
                    'connection' => $connection->name,
                    'host' => $connection->host,
                    'port' => $connection->port,
                    'reachable' => true,
                    'version' => $status['version'],
                    'inputs_count' => $status['inputs_count'],
                ];
            } catch (\Throwable $exception) {
                $results[] = [
                    'connection' => $connection->name,
                    'host' => $connection->host,
                    'port' => $connection->port,
                    'reachable' => false,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return $results;
    }
}
