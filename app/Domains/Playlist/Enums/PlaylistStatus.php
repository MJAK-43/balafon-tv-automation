<?php

namespace App\Domains\Playlist\Enums;

enum PlaylistStatus: string
{
    case DRAFT = 'DRAFT';
    case READY = 'READY';
    case ARCHIVED = 'ARCHIVED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
