<?php

namespace App\Domains\Vmix\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class VmixHttpClient
{
    public function getStatus(string $host, int $port, int $timeoutMs = 3000): string
    {
        return $this->request($host, $port, '/api', $timeoutMs);
    }

    public function getInputs(string $host, int $port, int $timeoutMs = 3000): string
    {
        return $this->request($host, $port, '/api', $timeoutMs);
    }

    public function healthCheck(string $host, int $port, int $timeoutMs = 3000): bool
    {
        $this->request($host, $port, '/api', $timeoutMs);

        return true;
    }

    public function sendCommand(string $host, int $port, string $command, array $parameters = [], int $timeoutMs = 3000): array
    {
        $startedAt = microtime(true);
        $requestUrl = $this->url($host, $port, '/api');

        try {
            $response = Http::timeout($timeoutMs / 1000)
                ->get($requestUrl, array_merge(['Function' => $command], $parameters));
        } catch (ConnectionException $exception) {
            return [
                'status' => 'failed',
                'response_code' => null,
                'response_body' => null,
                'request_url' => $requestUrl,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error_message' => $exception->getMessage(),
            ];
        }

        return [
            'status' => $response->successful() ? 'success' : 'failed',
            'response_code' => $response->status(),
            'response_body' => $response->body(),
            'request_url' => $requestUrl,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'error_message' => $response->successful() ? null : 'vMix command returned an unsuccessful response.',
        ];
    }

    private function request(string $host, int $port, string $path, int $timeoutMs): string
    {
        try {
            $response = Http::timeout($timeoutMs / 1000)->get($this->url($host, $port, $path));
        } catch (ConnectionException $exception) {
            throw new \RuntimeException('Unable to connect to vMix API.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new \RuntimeException('vMix API returned an unsuccessful response.');
        }

        return $response->body();
    }

    private function url(string $host, int $port, string $path): string
    {
        return sprintf('http://%s:%d%s', $host, $port, $path);
    }
}
