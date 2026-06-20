<?php

namespace App\Domains\Vmix\Providers;

use App\Domains\Vmix\DTOs\VmixInputDTO;
use App\Domains\Vmix\DTOs\VmixStatusDTO;
use App\Domains\Vmix\Models\VmixConnection;
use App\Domains\Vmix\Providers\Contracts\VmixProviderInterface;

class MockVmixProvider implements VmixProviderInterface
{
    public function getStatus(VmixConnection $connection): VmixStatusDTO
    {
        return new VmixStatusDTO(
            connected: true,
            version: '29.x-mock',
            edition: '4K Mock',
            activeInput: 'Opening Loop',
            previewInput: 'Lower Third',
            streaming: false,
            recording: false,
            external: false,
            fullscreen: false,
            inputs: [
                new VmixInputDTO('1', 'Opening Loop', 'Video', 'Paused'),
                new VmixInputDTO('2', 'Lower Third', 'Title', 'Stopped'),
            ],
            raw: ['mock' => true, 'connection' => $connection->name],
        );
    }

    public function getInputs(VmixConnection $connection): array
    {
        return $this->getStatus($connection)->toArray()['inputs'];
    }

    public function healthCheck(VmixConnection $connection): bool
    {
        return true;
    }

    public function sendCommand(VmixConnection $connection, string $command, array $parameters = []): array
    {
        return [
            'status' => 'success',
            'response_code' => 200,
            'response_body' => 'MOCK_OK',
            'request_url' => sprintf('mock://%s/%s', $connection->name, $command),
            'duration_ms' => 5,
            'error_message' => null,
            'mock' => true,
            'parameters' => $parameters,
        ];
    }
}
