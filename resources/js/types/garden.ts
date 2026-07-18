export type GardenVisibility =
    'private' | 'connections' | 'unlisted' | 'public';

/**
 * Countries::LIST aus dem Backend: { 'DE': 'Germany', 'AT': 'Austria', ... }
 */
export type Countries = Record<string, string>;

export type Garden = {
    id: number;
    name: string;
    description: string | null;
    visibility: GardenVisibility;
    size_sqm: string | null; // decimal-Cast → JSON-String
    country: string | null;
    region: string | null;
    archived_at: string | null;
    created_at: string;
    updated_at: string;
};
