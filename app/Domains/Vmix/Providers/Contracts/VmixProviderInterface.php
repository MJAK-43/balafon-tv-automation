<?php

namespace App\Domains\Vmix\Providers\Contracts;

use App\Domains\Vmix\DTOs\VmixStatusDTO;
use App\Domains\Vmix\Models\VmixConnection;

interface VmixProviderInterface
{
    public function getStatus(VmixConnection $connection): VmixStatusDTO;

    public function getInputs(VmixConnection $connection): array;

    public function healthCheck(VmixConnection $connection): bool;

    public function sendCommand(VmixConnection $connection, string $command, array $parameters = []): array;
}
