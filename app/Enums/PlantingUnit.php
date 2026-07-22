<?php

namespace App\Enums;

enum PlantingUnit: string
{
    case Pieces = 'pieces';
    case SquareMeters = 'square_meters';

    public function label(): string
    {
        return match ($this) {
            self::Pieces => 'Stück',
            self::SquareMeters => 'm²',
        };
    }
}
