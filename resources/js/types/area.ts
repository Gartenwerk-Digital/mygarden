export type AreaType =
    | 'lawn'
    | 'flower_bed'
    | 'vegetable_bed'
    | 'herb_bed'
    | 'raised_bed'
    | 'orchard'
    | 'hedge'
    | 'tree_area'
    | 'greenhouse'
    | 'terrace'
    | 'path'
    | 'pond'
    | 'garden_house'
    | 'compost'
    | 'other';

export type SunExposure = 'full_sun' | 'partial_shade' | 'shade';
export type SoilType = 'sandy' | 'loamy' | 'clay' | 'humus' | 'mixed';
export type MoistureLevel = 'dry' | 'normal' | 'moist' | 'wet';

export type AreaSummary = {
    id: number;
    name: string;
    type: AreaType;
    archived_at: string | null;
};

export type Area = {
    id: number;
    garden_id: number;
    parent_id: number | null;
    name: string;
    type: AreaType;
    description: string | null;
    size_sqm: string | null;
    sun_exposure: SunExposure | null;
    soil_type: SoilType | null;
    moisture_level: MoistureLevel | null;
    archived_at: string | null;
    created_at: string;
    updated_at: string;
    parent?: AreaSummary | null;
    children?: Area[];
    archivedChildren?: Area[];
};
