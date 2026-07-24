<?php

namespace App\Enums;

enum EntryType: string
{
    case Observation = 'observation';
    case Care = 'care';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Observation => 'Beobachtung',
            self::Care => 'Pflege',
            self::Note => 'Notiz',
        };
    }
}
