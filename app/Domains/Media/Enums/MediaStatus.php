<?php

namespace App\Domains\Media\Enums;

enum MediaStatus: string
{
    case READY = 'READY';
    case ARCHIVED = 'ARCHIVED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
