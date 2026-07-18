<?php

namespace App\Enums;

enum SoilType: string
{
    case Sandy = 'sandy';
    case Loamy = 'loamy';
    case Clay = 'clay';
    case Humus = 'humus';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Sandy => 'Sandig',
            self::Loamy => 'Lehmig',
            self::Clay => 'Tonig',
            self::Humus => 'Humusreich',
            self::Mixed => 'Gemischt',
        };
    }
}
