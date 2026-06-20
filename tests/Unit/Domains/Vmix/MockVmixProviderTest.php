<?php

namespace Tests\Unit\Domains\Vmix;

use App\Domains\Vmix\Models\VmixConnection;
use App\Domains\Vmix\Providers\MockVmixProvider;
use PHPUnit\Framework\TestCase;

class MockVmixProviderTest extends TestCase
{
    public function test_mock_provider_returns_status_payload(): void
    {
        $provider = new MockVmixProvider();
        $connection = new VmixConnection([
            'name' => 'Mock',
            'host' => 'mock',
            'port' => 8088,
            'timeout_ms' => 3000,
        ]);

        $status = $provider->getStatus($connection);

        $this->assertSame('29.x-mock', $status->version);
        $this->assertSame('4K Mock', $status->edition);
        $this->assertCount(2, $status->inputs);
    }
}
