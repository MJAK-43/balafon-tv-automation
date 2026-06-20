<?php

namespace App\Domains\Vmix\Providers;

use App\Domains\Vmix\Clients\VmixHttpClient;
use App\Domains\Vmix\DTOs\VmixInputDTO;
use App\Domains\Vmix\DTOs\VmixStatusDTO;
use App\Domains\Vmix\Models\VmixConnection;
use App\Domains\Vmix\Providers\Contracts\VmixProviderInterface;

class RealVmixProvider implements VmixProviderInterface
{
    public function __construct(
        private readonly VmixHttpClient $client,
    ) {
    }

    public function getStatus(VmixConnection $connection): VmixStatusDTO
    {
        $xml = simplexml_load_string($this->client->getStatus($connection->host, (int) $connection->port, (int) $connection->timeout_ms));

        if ($xml === false) {
            throw new \RuntimeException('Unable to parse vMix XML payload.');
        }

        $inputs = [];

        foreach ($xml->inputs->input ?? [] as $input) {
            $inputs[] = new VmixInputDTO(
                key: (string) ($input['key'] ?? ''),
                title: (string) ($input['title'] ?? $input),
                type: (string) ($input['type'] ?? ''),
                state: (string) ($input['state'] ?? ''),
            );
        }

        return new VmixStatusDTO(
            connected: true,
            version: (string) ($xml->version ?? null),
            edition: (string) ($xml->edition ?? null),
            activeInput: $this->findInputTitle($xml, (string) ($xml->active ?? '')),
            previewInput: $this->findInputTitle($xml, (string) ($xml->preview ?? '')),
            streaming: ((string) ($xml->streaming ?? 'False')) === 'True',
            recording: ((string) ($xml->recording ?? 'False')) === 'True',
            external: ((string) ($xml->external ?? 'False')) === 'True',
            fullscreen: ((string) ($xml->fullscreen ?? 'False')) === 'True',
            inputs: $inputs,
            raw: [
                'xml' => (string) $xml->asXML(),
            ],
        );
    }

    public function getInputs(VmixConnection $connection): array
    {
        return $this->getStatus($connection)->toArray()['inputs'];
    }

    public function healthCheck(VmixConnection $connection): bool
    {
        return $this->client->healthCheck($connection->host, (int) $connection->port, (int) $connection->timeout_ms);
    }

    public function sendCommand(VmixConnection $connection, string $command, array $parameters = []): array
    {
        return $this->client->sendCommand($connection->host, (int) $connection->port, $command, $parameters, (int) $connection->timeout_ms);
    }

    private function findInputTitle(\SimpleXMLElement $xml, string $number): ?string
    {
        foreach ($xml->inputs->input ?? [] as $input) {
            if ((string) ($input['number'] ?? '') === $number) {
                return (string) ($input['title'] ?? $input);
            }
        }

        return $number !== '' ? $number : null;
    }
}
