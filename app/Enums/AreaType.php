<?php

namespace App\Enums;

enum AreaType: string
{
    case Lawn = 'lawn';
    case FlowerBed = 'flower_bed';
    case VegetableBed = 'vegetable_bed';
    case HerbBed = 'herb_bed';
    case RaisedBed = 'raised_bed';
    case Orchard = 'orchard';
    case Hedge = 'hedge';
    case TreeArea = 'tree_area';
    case Greenhouse = 'greenhouse';
    case Terrace = 'terrace';
    case Path = 'path';
    case Pond = 'pond';
    case GardenHouse = 'garden_house';
    case Compost = 'compost';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Lawn => 'Rasen',
            self::FlowerBed => 'Blumenbeet',
            self::VegetableBed => 'Gemüsebeet',
            self::HerbBed => 'Kräuterbeet',
            self::RaisedBed => 'Hochbeet',
            self::Orchard => 'Obstgarten',
            self::Hedge => 'Hecke',
            self::TreeArea => 'Baumbereich',
            self::Greenhouse => 'Gewächshaus',
            self::Terrace => 'Terrasse',
            self::Path => 'Weg',
            self::Pond => 'Teich',
            self::GardenHouse => 'Gartenhaus',
            self::Compost => 'Kompost',
            self::Other => 'Sonstiges',
        };
    }
}
