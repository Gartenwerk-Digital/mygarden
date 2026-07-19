import type { MoistureLevel, SoilType, SunExposure } from '@/types/area';

export type PlantCategory =
    | 'vegetable'
    | 'herb'
    | 'fruit'
    | 'flower'
    | 'shrub'
    | 'tree'
    | 'lawn'
    | 'hedge'
    | 'aquatic'
    | 'houseplant'
    | 'climber'
    | 'other';

export type PlantLifecycle = 'annual' | 'biennial' | 'perennial';

export type CatalogOrigin = 'system' | 'user';

export type EnumOption = { value: string; label: string };

export type PlantSpecies = {
    id: number;
    origin: CatalogOrigin;
    created_by: number | null;
    common_name: string;
    botanical_name: string | null;
    alternate_name: string | null;
    category: PlantCategory;
    description: string | null;
    lifecycle: PlantLifecycle | null;
    hardiness_note: string | null;
    sun_exposures: SunExposure[] | null;
    soil_types: SoilType[] | null;
    moisture_levels: MoistureLevel[] | null;
    height_min_cm: number | null;
    height_max_cm: number | null;
    archived_at: string | null;
    created_at: string;
    updated_at: string;
    varieties?: PlantVariety[];
};

export type PlantSpeciesSummary = Pick<
    PlantSpecies,
    'id' | 'common_name' | 'botanical_name' | 'category' | 'archived_at'
>;

export type PlantVariety = {
    id: number;
    plant_species_id: number;
    origin: CatalogOrigin;
    created_by: number | null;
    name: string;
    breeder: string | null;
    year_introduced: number | null;
    description: string | null;
    archived_at: string | null;
    created_at: string;
    updated_at: string;
};
