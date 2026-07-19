<?php

namespace App\Enums;

enum CatalogOrigin: string
{
    case System = 'system';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::System => 'System',
            self::User => 'Community',
        };
    }
}
