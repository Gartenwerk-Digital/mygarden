import type { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { EnumOption, PlantCategory, PlantLifecycle } from '@/types/plant';

export type PlantSpeciesFormData = {
    common_name: string;
    botanical_name: string;
    alternate_name: string;
    category: PlantCategory | '';
    description: string;
    lifecycle: PlantLifecycle | '';
    hardiness_note: string;
    sun_exposures: string[];
    soil_types: string[];
    moisture_levels: string[];
    height_min_cm: string;
    height_max_cm: string;
};

type Props = {
    data: PlantSpeciesFormData;
    errors: Partial<
        Record<
            | keyof PlantSpeciesFormData
            | 'sun_exposures.*'
            | 'soil_types.*'
            | 'moisture_levels.*',
            string
        >
    >;
    processing: boolean;
    categories: EnumOption[];
    lifecycles: EnumOption[];
    sunExposures: EnumOption[];
    soilTypes: EnumOption[];
    moistureLevels: EnumOption[];
    setData: <K extends keyof PlantSpeciesFormData>(
        key: K,
        value: PlantSpeciesFormData[K],
    ) => void;
    onSubmit: FormEventHandler;
    submitLabel: string;
    cancelHref: string;
};

export function PlantSpeciesForm({
    data,
    errors,
    processing,
    categories,
    lifecycles,
    sunExposures,
    soilTypes,
    moistureLevels,
    setData,
    onSubmit,
    submitLabel,
    cancelHref,
}: Props) {
    const toggleArrayValue = (
        field: 'sun_exposures' | 'soil_types' | 'moisture_levels',
        value: string,
    ) => {
        const current = data[field];

        if (current.includes(value)) {
            setData(
                field,
                current.filter((v) => v !== value),
            );
        } else {
            setData(field, [...current, value]);
        }
    };

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div className="grid gap-2">
                <Label htmlFor="common_name">Name</Label>
                <Input
                    id="common_name"
                    value={data.common_name}
                    onChange={(e) => setData('common_name', e.target.value)}
                    maxLength={150}
                    required
                    autoFocus
                />
                <p className="text-xs text-muted-foreground">
                    Suche zuerst im Katalog nach dieser Pflanze, um Duplikate zu
                    vermeiden.
                </p>
                <InputError message={errors.common_name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="botanical_name">
                    Botanischer Name{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="botanical_name"
                    value={data.botanical_name}
                    onChange={(e) => setData('botanical_name', e.target.value)}
                    maxLength={200}
                    placeholder="z. B. Lavandula angustifolia"
                />
                <p className="text-xs text-muted-foreground">
                    Muss eindeutig sein – gleicher botanischer Name wird nur
                    einmal akzeptiert.
                </p>
                <InputError message={errors.botanical_name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="alternate_name">
                    Alternativer Name{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="alternate_name"
                    value={data.alternate_name}
                    onChange={(e) => setData('alternate_name', e.target.value)}
                    maxLength={150}
                />
                <InputError message={errors.alternate_name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="category">Kategorie</Label>
                <Select
                    value={data.category}
                    onValueChange={(value) =>
                        setData('category', value as PlantCategory)
                    }
                >
                    <SelectTrigger id="category">
                        <SelectValue placeholder="Kategorie auswählen …" />
                    </SelectTrigger>
                    <SelectContent>
                        {categories.map(({ value, label }) => (
                            <SelectItem key={value} value={value}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.category} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">
                    Beschreibung{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <textarea
                    id="description"
                    rows={3}
                    maxLength={5000}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                />
                <InputError message={errors.description} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="lifecycle">
                    Lebenszyklus{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Select
                    value={data.lifecycle}
                    onValueChange={(value) =>
                        setData('lifecycle', value as PlantLifecycle | '')
                    }
                >
                    <SelectTrigger id="lifecycle">
                        <SelectValue placeholder="Lebenszyklus auswählen …" />
                    </SelectTrigger>
                    <SelectContent>
                        {lifecycles.map(({ value, label }) => (
                            <SelectItem key={value} value={value}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.lifecycle} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="hardiness_note">
                    Winterhärte{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="hardiness_note"
                    value={data.hardiness_note}
                    onChange={(e) => setData('hardiness_note', e.target.value)}
                    maxLength={255}
                    placeholder="z. B. winterhart bis −15 °C"
                />
                <InputError message={errors.hardiness_note} />
            </div>

            <fieldset className="grid gap-2">
                <legend className="text-sm font-medium">
                    Sonneneinstrahlung{' '}
                    <span className="text-muted-foreground">
                        (optional, Mehrfachauswahl)
                    </span>
                </legend>
                <div className="flex flex-wrap gap-3">
                    {sunExposures.map(({ value, label }) => (
                        <label
                            key={value}
                            className="flex cursor-pointer items-center gap-2"
                        >
                            <Checkbox
                                checked={data.sun_exposures.includes(value)}
                                onCheckedChange={() =>
                                    toggleArrayValue('sun_exposures', value)
                                }
                            />
                            <span className="text-sm">{label}</span>
                        </label>
                    ))}
                </div>
                <InputError
                    message={errors['sun_exposures.*'] ?? errors.sun_exposures}
                />
            </fieldset>

            <fieldset className="grid gap-2">
                <legend className="text-sm font-medium">
                    Bodenart{' '}
                    <span className="text-muted-foreground">
                        (optional, Mehrfachauswahl)
                    </span>
                </legend>
                <div className="flex flex-wrap gap-3">
                    {soilTypes.map(({ value, label }) => (
                        <label
                            key={value}
                            className="flex cursor-pointer items-center gap-2"
                        >
                            <Checkbox
                                checked={data.soil_types.includes(value)}
                                onCheckedChange={() =>
                                    toggleArrayValue('soil_types', value)
                                }
                            />
                            <span className="text-sm">{label}</span>
                        </label>
                    ))}
                </div>
                <InputError
                    message={errors['soil_types.*'] ?? errors.soil_types}
                />
            </fieldset>

            <fieldset className="grid gap-2">
                <legend className="text-sm font-medium">
                    Feuchtigkeit{' '}
                    <span className="text-muted-foreground">
                        (optional, Mehrfachauswahl)
                    </span>
                </legend>
                <div className="flex flex-wrap gap-3">
                    {moistureLevels.map(({ value, label }) => (
                        <label
                            key={value}
                            className="flex cursor-pointer items-center gap-2"
                        >
                            <Checkbox
                                checked={data.moisture_levels.includes(value)}
                                onCheckedChange={() =>
                                    toggleArrayValue('moisture_levels', value)
                                }
                            />
                            <span className="text-sm">{label}</span>
                        </label>
                    ))}
                </div>
                <InputError
                    message={
                        errors['moisture_levels.*'] ?? errors.moisture_levels
                    }
                />
            </fieldset>

            <div className="grid gap-2">
                <Label>
                    Wuchshöhe in cm{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <div className="flex items-center gap-3">
                    <div className="grid flex-1 gap-1">
                        <span className="text-xs text-muted-foreground">
                            Minimum
                        </span>
                        <Input
                            type="number"
                            min="1"
                            max="60000"
                            step="1"
                            value={data.height_min_cm}
                            onChange={(e) =>
                                setData('height_min_cm', e.target.value)
                            }
                            placeholder="z. B. 30"
                        />
                        <InputError message={errors.height_min_cm} />
                    </div>
                    <span className="mt-4 text-muted-foreground">–</span>
                    <div className="grid flex-1 gap-1">
                        <span className="text-xs text-muted-foreground">
                            Maximum
                        </span>
                        <Input
                            type="number"
                            min="1"
                            max="60000"
                            step="1"
                            value={data.height_max_cm}
                            onChange={(e) =>
                                setData('height_max_cm', e.target.value)
                            }
                            placeholder="z. B. 60"
                        />
                        <InputError message={errors.height_max_cm} />
                    </div>
                </div>
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
