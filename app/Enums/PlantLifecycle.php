<?php

namespace App\Enums;

enum PlantLifecycle: string
{
    case Annual = 'annual';
    case Biennial = 'biennial';
    case Perennial = 'perennial';

    public function label(): string
    {
        return match ($this) {
            self::Annual => 'Einjährig',
            self::Biennial => 'Zweijährig',
            self::Perennial => 'Mehrjährig',
        };
    }
}
