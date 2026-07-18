import type { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
    areaTypeLabels,
    moistureLevelLabels,
    soilTypeLabels,
    sunExposureLabels,
} from '@/lib/area-type-icons';
import type {
    AreaSummary,
    AreaType,
    MoistureLevel,
    SoilType,
    SunExposure,
} from '@/types/area';

export type AreaFormData = {
    name: string;
    type: AreaType;
    description: string;
    size_sqm: string;
    sun_exposure: SunExposure | '';
    soil_type: SoilType | '';
    moisture_level: MoistureLevel | '';
    parent_id: string;
};

type Props = {
    data: AreaFormData;
    errors: Partial<Record<keyof AreaFormData, string>>;
    processing: boolean;
    rootAreas: AreaSummary[];
    hasChildren?: boolean;
    setData: <K extends keyof AreaFormData>(
        key: K,
        value: AreaFormData[K],
    ) => void;
    onSubmit: FormEventHandler;
    submitLabel: string;
    cancelHref: string;
};

const AREA_TYPES = Object.entries(areaTypeLabels) as [AreaType, string][];
const SUN_OPTIONS = Object.entries(sunExposureLabels) as [
    SunExposure,
    string,
][];
const SOIL_OPTIONS = Object.entries(soilTypeLabels) as [SoilType, string][];
const MOISTURE_OPTIONS = Object.entries(moistureLevelLabels) as [
    MoistureLevel,
    string,
][];

export function AreaForm({
    data,
    errors,
    processing,
    rootAreas,
    hasChildren = false,
    setData,
    onSubmit,
    submitLabel,
    cancelHref,
}: Props) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div className="grid gap-2">
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    maxLength={100}
                    required
                    autoFocus
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="type">Typ</Label>
                <Select
                    value={data.type}
                    onValueChange={(value) =>
                        setData('type', value as AreaType)
                    }
                >
                    <SelectTrigger id="type">
                        <SelectValue placeholder="Typ auswählen …" />
                    </SelectTrigger>
                    <SelectContent>
                        {AREA_TYPES.map(([value, label]) => (
                            <SelectItem key={value} value={value}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.type} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">
                    Beschreibung{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <textarea
                    id="description"
                    name="description"
                    rows={3}
                    maxLength={1000}
                    placeholder="Beschreibe diesen Bereich …"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                />
                <InputError message={errors.description} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="size_sqm">
                    Größe in m²{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="size_sqm"
                    type="number"
                    min="0.01"
                    max="99999999.99"
                    step="0.01"
                    value={data.size_sqm}
                    onChange={(e) => setData('size_sqm', e.target.value)}
                    placeholder="z. B. 24.50"
                />
                <InputError message={errors.size_sqm} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="sun_exposure">
                    Sonneneinstrahlung{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Select
                    value={data.sun_exposure}
                    onValueChange={(value) =>
                        setData('sun_exposure', value as SunExposure | '')
                    }
                >
                    <SelectTrigger id="sun_exposure">
                        <SelectValue placeholder="Sonneneinstrahlung auswählen …" />
                    </SelectTrigger>
                    <SelectContent>
                        {SUN_OPTIONS.map(([value, label]) => (
                            <SelectItem key={value} value={value}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.sun_exposure} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="soil_type">
                    Bodenart{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Select
                    value={data.soil_type}
                    onValueChange={(value) =>
                        setData('soil_type', value as SoilType | '')
                    }
                >
                    <SelectTrigger id="soil_type">
                        <SelectValue placeholder="Bodenart auswählen …" />
                    </SelectTrigger>
                    <SelectContent>
                        {SOIL_OPTIONS.map(([value, label]) => (
                            <SelectItem key={value} value={value}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.soil_type} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="moisture_level">
                    Feuchtigkeit{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Select
                    value={data.moisture_level}
                    onValueChange={(value) =>
                        setData('moisture_level', value as MoistureLevel | '')
                    }
                >
                    <SelectTrigger id="moisture_level">
                        <SelectValue placeholder="Feuchtigkeitsstufe auswählen …" />
                    </SelectTrigger>
                    <SelectContent>
                        {MOISTURE_OPTIONS.map(([value, label]) => (
                            <SelectItem key={value} value={value}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.moisture_level} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="parent_id">
                    Übergeordneter Bereich{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Select
                    value={data.parent_id}
                    onValueChange={(value) => setData('parent_id', value)}
                    disabled={hasChildren}
                >
                    <SelectTrigger id="parent_id">
                        <SelectValue placeholder="Kein übergeordneter Bereich" />
                    </SelectTrigger>
                    <SelectContent>
                        {rootAreas.map((area) => (
                            <SelectItem key={area.id} value={String(area.id)}>
                                {area.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {hasChildren && (
                    <p className="text-xs text-muted-foreground">
                        Dieser Bereich enthält Unterbereiche und kann nicht
                        selbst einem übergeordneten Bereich zugeordnet werden.
                    </p>
                )}
                <InputError message={errors.parent_id} />
            </div>

            <div className="flex items-center gap-3">
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner className="mr-2 size-4" />}
                    {submitLabel}
                </Button>
                <Button type="button" variant="outline" asChild>
                    <a href={cancelHref}>Abbrechen</a>
                </Button>
            </div>
        </form>
    );
}
