import {
    Apple,
    Bird,
    Droplets,
    Flower2,
    Leaf,
    LeafyGreen,
    Salad,
    Sprout,
    Trees,
    Waves,
    Wind,
    House,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import type { PlantCategory } from '@/types/plant';

export const plantCategoryIcons: Record<PlantCategory, LucideIcon> = {
    vegetable: Salad,
    herb: LeafyGreen,
    fruit: Apple,
    flower: Flower2,
    shrub: Sprout,
    tree: Trees,
    lawn: Leaf,
    hedge: Wind,
    aquatic: Waves,
    houseplant: House,
    climber: Bird,
    other: Droplets,
};
