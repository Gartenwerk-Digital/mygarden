import {
    Archive,
    Boxes,
    Flower2,
    FlowerIcon,
    Home,
    Layers,
    Leaf,
    Sprout,
    Trees,
    Waves,
    Wind,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import type { AreaType } from '@/types/area';

export const areaTypeIcons: Record<AreaType, LucideIcon> = {
    lawn: Sprout,
    flower_bed: Flower2,
    vegetable_bed: Leaf,
    herb_bed: FlowerIcon,
    raised_bed: Layers,
    orchard: Trees,
    hedge: Wind,
    tree_area: Trees,
    greenhouse: Home,
    terrace: Boxes,
    path: Waves,
    pond: Waves,
    garden_house: Home,
    compost: Archive,
    other: Leaf,
};

export const areaTypeLabels: Record<AreaType, string> = {
    lawn: 'Rasen',
    flower_bed: 'Blumenbeet',
    vegetable_bed: 'Gemüsebeet',
    herb_bed: 'Kräuterbeet',
    raised_bed: 'Hochbeet',
    orchard: 'Obstgarten',
    hedge: 'Hecke',
    tree_area: 'Baumbereich',
    greenhouse: 'Gewächshaus',
    terrace: 'Terrasse',
    path: 'Weg',
    pond: 'Teich',
    garden_house: 'Gartenhaus',
    compost: 'Kompost',
    other: 'Sonstiges',
};

export const sunExposureLabels: Record<string, string> = {
    full_sun: 'Volle Sonne',
    partial_shade: 'Halbschatten',
    shade: 'Schatten',
};

export const soilTypeLabels: Record<string, string> = {
    sandy: 'Sandig',
    loamy: 'Lehmig',
    clay: 'Tonig',
    humus: 'Humusreich',
    mixed: 'Gemischt',
};

export const moistureLevelLabels: Record<string, string> = {
    dry: 'Trocken',
    normal: 'Normal',
    moist: 'Feucht',
    wet: 'Nass',
};
