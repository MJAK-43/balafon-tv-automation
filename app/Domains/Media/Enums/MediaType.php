<?php

namespace App\Domains\Media\Enums;

enum MediaType: string
{
    case PROGRAM = 'PROGRAM';
    case MOVIE = 'MOVIE';
    case ADVERTISEMENT = 'ADVERTISEMENT';
    case JINGLE = 'JINGLE';
    case LIVE_PLACEHOLDER = 'LIVE_PLACEHOLDER';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
