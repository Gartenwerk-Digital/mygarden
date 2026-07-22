import type {
    CatalogOrigin,
    CatalogStatus,
    PlantCategory,
} from '@/types/plant';

export type PlantingStatus = 'planned' | 'planted' | 'removed';
export type PlantingUnit = 'pieces' | 'square_meters';

export type PlantingSpeciesSummary = {
    id: number;
    common_name: string;
    category: PlantCategory;
    archived_at: string | null;
    deleted_at: string | null;
};

export type PlantingVarietySummary = {
    id: number;
    name: string;
    archived_at: string | null;
    deleted_at: string | null;
};

export type PlantingAreaSummary = {
    id: number;
    name: string;
    archived_at: string | null;
    deleted_at: string | null;
};

export type GardenPlanting = {
    id: number;
    garden_id: number;
    area_id: number | null;
    plant_species_id: number;
    plant_variety_id: number | null;
    status: PlantingStatus;
    removed_at: string | null;
    quantity: string | null;
    quantity_unit: PlantingUnit | null;
    nickname: string | null;
    notes: string | null;
    planted_at: string | null;
    acquired_at: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    species?: PlantingSpeciesSummary | null;
    variety?: PlantingVarietySummary | null;
    area?: PlantingAreaSummary | null;
};

export type GardenPlantingSummary = {
    id: number;
    garden_id: number;
    area_id: number | null;
    plant_species_id: number;
    plant_variety_id: number | null;
    status: PlantingStatus;
    removed_at: string | null;
    quantity: string | null;
    quantity_unit: PlantingUnit | null;
    nickname: string | null;
    species: PlantingSpeciesSummary;
    variety: PlantingVarietySummary | null;
    area: PlantingAreaSummary | null;
};

export type PlantSpeciesOption = {
    id: number;
    common_name: string;
    botanical_name: string | null;
    alternate_name: string | null;
    origin: CatalogOrigin;
    catalog_status: CatalogStatus;
    varieties: PlantVarietyOption[];
};

export type PlantVarietyOption = {
    id: number;
    name: string;
    catalog_status: CatalogStatus;
};

export type PlantingFormData = {
    plant_species_id: number | '';
    plant_variety_id: number | '';
    area_id: number | '';
    status: PlantingStatus;
    quantity: string;
    quantity_unit: PlantingUnit | '';
    nickname: string;
    notes: string;
    planted_at: string;
    acquired_at: string;
};

export const plantingStatusLabels: Record<PlantingStatus, string> = {
    planned: 'Geplant',
    planted: 'Gepflanzt',
    removed: 'Entfernt',
};

export const plantingUnitLabels: Record<PlantingUnit, string> = {
    pieces: 'Stück',
    square_meters: 'm²',
};

export const plantingStatusSortOrder: Record<PlantingStatus, number> = {
    planted: 0,
    planned: 1,
    removed: 2,
};

export function formatQuantity(
    quantity: string | null,
    unit: PlantingUnit | null,
): string | null {
    if (quantity === null || unit === null) {
        return null;
    }

    if (unit === 'pieces') {
        return `${parseInt(quantity, 10)} Stück`;
    }

    return `${parseFloat(quantity).toLocaleString('de-DE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} m²`;
}
