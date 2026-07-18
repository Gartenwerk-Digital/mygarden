<?php

namespace App\Enums;

enum MoistureLevel: string
{
    case Dry = 'dry';
    case Normal = 'normal';
    case Moist = 'moist';
    case Wet = 'wet';

    public function label(): string
    {
        return match ($this) {
            self::Dry => 'Trocken',
            self::Normal => 'Normal',
            self::Moist => 'Feucht',
            self::Wet => 'Nass',
        };
    }
}
