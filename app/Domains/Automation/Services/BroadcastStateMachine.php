<?php

namespace App\Domains\Automation\Services;

use App\Domains\Automation\Enums\BroadcastState;
use InvalidArgumentException;

class BroadcastStateMachine
{
    private const TRANSITIONS = [
        'PENDING' => ['PREPARING', 'SKIPPED', 'FAILED'],
        'PREPARING' => ['ON_AIR', 'FAILED', 'SKIPPED'],
        'ON_AIR' => ['COMPLETED', 'FAILED', 'SKIPPED'],
        'COMPLETED' => [],
        'FAILED' => [],
        'SKIPPED' => [],
    ];

    public function assertTransition(string $from, string $to): void
    {
        if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new InvalidArgumentException("Invalid broadcast transition: {$from} -> {$to}");
        }
    }

    public function transition(string $from, string $to): string
    {
        $this->assertTransition($from, $to);

        return $to;
    }

    public function initial(): string
    {
        return BroadcastState::PENDING->value;
    }
}
