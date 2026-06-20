<?php

namespace App\Domains\Vmix\Services;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Vmix\Models\VmixCommandLog;
use App\Domains\Vmix\Models\VmixConnection;
use App\Domains\Vmix\Providers\Contracts\VmixProviderInterface;
use App\Domains\Vmix\Repositories\Contracts\VmixConnectionRepositoryInterface;
use Illuminate\Support\Str;

class VmixApiService
{
    public function __construct(
        private readonly VmixConnectionRepositoryInterface $connections,
        private readonly VmixProviderInterface $provider,
        private readonly AuditService $audit,
    ) {
    }

    public function listConnections()
    {
        return $this->connections->all();
    }

    public function updateConnection(string $uuid, array $payload): VmixConnection
    {
        $connection = $this->connections->findByUuid($uuid);
        abort_if($connection === null, 404, 'vMix connection not found.');

        $connection->fill($payload);
        $connection->save();

        return $connection->refresh();
    }

    public function getStatus(?string $uuid = null): array
    {
        $connection = $uuid
            ? $this->connections->findByUuid($uuid)
            : $this->connections->allActive()->first();

        abort_if($connection === null, 404, 'vMix connection not found.');

        return $this->provider->getStatus($connection)->toArray();
    }

    public function getInputs(?string $uuid = null): array
    {
        return $this->getStatus($uuid)['inputs'];
    }

    public function testConnection(string $uuid, ?int $actorId = null): array
    {
        $connection = $this->connections->findByUuid($uuid);
        abort_if($connection === null, 404, 'vMix connection not found.');

        $status = $this->provider->getStatus($connection);

        $payload = [
            'success' => true,
            'version' => $status->version,
            'inputs_count' => count($status->inputs),
            'connected' => $status->connected,
        ];

        $this->audit->log('vmix.connection_tested', 'vmix_connection', (string) $connection->id, $payload, $actorId, $actorId ? 'user' : 'system');

        return $payload;
    }

    public function playTest(?int $actorId = null): array
    {
        $connection = $this->connections->allActive()->first();
        abort_if($connection === null, 404, 'No active vMix connection configured.');

        $response = $this->provider->sendCommand($connection, 'Play');

        VmixCommandLog::create([
            'uuid' => (string) Str::uuid(),
            'vmix_connection_id' => $connection->id,
            'command_name' => 'Play',
            'request_url' => $response['request_url'] ?? '',
            'request_payload' => ['command' => 'Play'],
            'response_code' => $response['response_code'] ?? null,
            'response_body' => $response['response_body'] ?? null,
            'status' => $response['status'] ?? 'failed',
            'duration_ms' => $response['duration_ms'] ?? null,
            'error_message' => $response['error_message'] ?? null,
            'executed_at' => now(),
        ]);

        $this->audit->log('vmix.play_test', 'vmix_connection', (string) $connection->id, $response, $actorId, $actorId ? 'user' : 'system');

        return $response;
    }
}
