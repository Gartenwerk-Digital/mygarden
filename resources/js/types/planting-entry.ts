export type EntryType = 'observation' | 'care' | 'note';

export type PlantingEntryImage = {
    id: number;
    planting_entry_id: number;
    mime_type: string;
    size: number;
    sort_order: number;
    created_at: string;
};

export type PlantingEntry = {
    id: number;
    garden_planting_id: number;
    entry_type: EntryType;
    notes: string | null;
    occurred_at: string;
    created_at: string;
    images: PlantingEntryImage[];
};

export type PlantingEntryFormData = {
    entry_type: EntryType;
    notes: string;
    occurred_at: string;
    images: File[];
    remove_image_ids: number[];
};

export type EntryTypeOption = {
    value: EntryType;
    label: string;
};

export const entryTypeLabels: Record<EntryType, string> = {
    observation: 'Beobachtung',
    care: 'Pflege',
    note: 'Notiz',
};
