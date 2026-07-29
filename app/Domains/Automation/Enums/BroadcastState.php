<?php

namespace App\Domains\Automation\Enums;

enum BroadcastState: string
{
    case PENDING = 'PENDING';
    case PREPARING = 'PREPARING';
    case ON_AIR = 'ON_AIR';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
    case SKIPPED = 'SKIPPED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
