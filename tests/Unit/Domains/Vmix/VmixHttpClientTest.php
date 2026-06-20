<?php

namespace Tests\Unit\Domains\Vmix;

use App\Domains\Vmix\Clients\VmixHttpClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VmixHttpClientTest extends TestCase
{
    public function test_get_status_returns_response_body(): void
    {
        Http::fake([
            'http://host.docker.internal:8088/api' => Http::response('<vmix><version>29</version></vmix>', 200),
        ]);

        $client = new VmixHttpClient();

        $body = $client->getStatus('host.docker.internal', 8088, 3000);

        $this->assertStringContainsString('<version>29</version>', $body);
    }

    public function test_send_command_returns_extended_loggable_payload(): void
    {
        Http::fake([
            'http://host.docker.internal:8088/api?Function=Play' => Http::response('OK', 200),
            'http://host.docker.internal:8088/api' => Http::response('OK', 200),
        ]);

        $client = new VmixHttpClient();

        $payload = $client->sendCommand('host.docker.internal', 8088, 'Play');

        $this->assertSame('success', $payload['status']);
        $this->assertArrayHasKey('request_url', $payload);
        $this->assertArrayHasKey('duration_ms', $payload);
        $this->assertArrayHasKey('error_message', $payload);
    }
}
