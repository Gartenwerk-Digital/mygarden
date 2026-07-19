<?php

namespace App\Enums;

enum PlantCategory: string
{
    case Vegetable = 'vegetable';
    case Herb = 'herb';
    case Fruit = 'fruit';
    case Flower = 'flower';
    case Shrub = 'shrub';
    case Tree = 'tree';
    case Lawn = 'lawn';
    case Hedge = 'hedge';
    case Aquatic = 'aquatic';
    case Houseplant = 'houseplant';
    case Climber = 'climber';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Vegetable => 'Gemüse',
            self::Herb => 'Kräuter',
            self::Fruit => 'Obst & Früchte',
            self::Flower => 'Blüh- & Zierpflanzen',
            self::Shrub => 'Sträucher',
            self::Tree => 'Bäume',
            self::Lawn => 'Rasen & Gräser',
            self::Hedge => 'Hecken',
            self::Aquatic => 'Wasser- & Uferpflanzen',
            self::Houseplant => 'Zimmerpflanzen',
            self::Climber => 'Kletterpflanzen',
            self::Other => 'Sonstiges',
        };
    }
}
