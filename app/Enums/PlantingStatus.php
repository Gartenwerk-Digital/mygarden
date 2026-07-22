<?php

namespace App\Enums;

enum PlantingStatus: string
{
    case Planned = 'planned';
    case Planted = 'planted';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Geplant',
            self::Planted => 'Gepflanzt',
            self::Removed => 'Entfernt',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Planted => 0,
            self::Planned => 1,
            self::Removed => 2,
        };
    }
}
