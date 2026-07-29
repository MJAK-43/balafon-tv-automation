<?php

namespace Tests\Unit\Domains\Automation;

use App\Domains\Automation\Services\BroadcastStateMachine;
use InvalidArgumentException;
use Tests\TestCase;

class BroadcastStateMachineTest extends TestCase
{
    public function test_it_allows_valid_broadcast_transitions(): void
    {
        $machine = new BroadcastStateMachine();

        $this->assertSame('PREPARING', $machine->transition('PENDING', 'PREPARING'));
        $this->assertSame('ON_AIR', $machine->transition('PREPARING', 'ON_AIR'));
        $this->assertSame('COMPLETED', $machine->transition('ON_AIR', 'COMPLETED'));
    }

    public function test_it_rejects_invalid_broadcast_transitions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new BroadcastStateMachine())->transition('PENDING', 'COMPLETED');
    }
}
