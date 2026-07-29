<?php

namespace App\Domains\Scheduling\Enums;

enum ScheduleStatus: string
{
    case DRAFT = 'DRAFT';
    case SCHEDULED = 'SCHEDULED';
    case ON_AIR = 'ON_AIR';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
