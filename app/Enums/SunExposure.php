<?php

namespace App\Enums;

enum SunExposure: string
{
    case FullSun = 'full_sun';
    case PartialShade = 'partial_shade';
    case Shade = 'shade';

    public function label(): string
    {
        return match ($this) {
            self::FullSun => 'Volle Sonne',
            self::PartialShade => 'Halbschatten',
            self::Shade => 'Schatten',
        };
    }
}
