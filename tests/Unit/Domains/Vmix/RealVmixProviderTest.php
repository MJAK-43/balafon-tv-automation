<?php

namespace Tests\Unit\Domains\Vmix;

use App\Domains\Vmix\Clients\VmixHttpClient;
use App\Domains\Vmix\Models\VmixConnection;
use App\Domains\Vmix\Providers\RealVmixProvider;
use PHPUnit\Framework\TestCase;

class RealVmixProviderTest extends TestCase
{
    public function test_real_provider_parses_extended_vmix_xml(): void
    {
        $client = new class extends VmixHttpClient
        {
            public function getStatus(string $host, int $port, int $timeoutMs = 3000): string
            {
                return <<<'XML'
<vmix>
    <version>29.0.0.1</version>
    <edition>HD</edition>
    <active>1</active>
    <preview>2</preview>
    <streaming>True</streaming>
    <recording>False</recording>
    <external>True</external>
    <fullscreen>False</fullscreen>
    <inputs>
        <input key="abc" number="1" title="Camera 1" type="Capture" state="Running">Camera 1</input>
        <input key="def" number="2" title="Clip A" type="Video" state="Paused">Clip A</input>
    </inputs>
</vmix>
XML;
            }
        };

        $provider = new RealVmixProvider($client);
        $connection = new VmixConnection([
            'host' => 'host.docker.internal',
            'port' => 8088,
            'timeout_ms' => 3000,
        ]);

        $status = $provider->getStatus($connection);

        $this->assertSame('29.0.0.1', $status->version);
        $this->assertSame('HD', $status->edition);
        $this->assertSame('Camera 1', $status->activeInput);
        $this->assertSame('Clip A', $status->previewInput);
        $this->assertTrue($status->streaming);
        $this->assertFalse($status->recording);
        $this->assertTrue($status->external);
        $this->assertFalse($status->fullscreen);
        $this->assertCount(2, $status->inputs);
    }
}
