<?php

namespace App\Domains\Branding\Enums;

enum BrandingAssetType: string
{
    case LOGO = 'LOGO';
    case ANNOUNCEMENT_TEXT = 'ANNOUNCEMENT_TEXT';
    case ANNOUNCEMENT_VIDEO = 'ANNOUNCEMENT_VIDEO';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function announcementValues(): array
    {
        return [
            self::ANNOUNCEMENT_TEXT->value,
            self::ANNOUNCEMENT_VIDEO->value,
        ];
    }
}
