<?php

namespace App\Enums;

enum GardenVisibility: string
{
    case Private = 'private';
    case Connections = 'connections';
    case Unlisted = 'unlisted';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Privat',
            self::Connections => 'Kontakte',
            self::Unlisted => 'Versteckt',
            self::Public => 'Öffentlich',
        };
    }
}
