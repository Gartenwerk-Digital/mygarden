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
import type { Countries, GardenVisibility } from '@/types/garden';

export type GardenFormData = {
    name: string;
    description: string;
    visibility: GardenVisibility;
    size_sqm: string;
    country: string;
    region: string;
};

type Props = {
    data: GardenFormData;
    errors: Partial<Record<keyof GardenFormData, string>>;
    processing: boolean;
    countries: Countries;
    setData: <K extends keyof GardenFormData>(
        key: K,
        value: GardenFormData[K],
    ) => void;
    onSubmit: FormEventHandler;
    submitLabel: string;
    cancelHref: string;
};

const VISIBILITY_OPTIONS: {
    value: GardenVisibility;
    label: string;
    description: string;
}[] = [
    {
        value: 'private',
        label: 'Privat',
        description: 'Nur du kannst diesen Garten sehen.',
    },
    {
        value: 'connections',
        label: 'Kontakte',
        description: 'Nur deine Kontakte können diesen Garten sehen.',
    },
    {
        value: 'unlisted',
        label: 'Versteckt',
        description:
            'Nicht auffindbar, aber über einen direkten Link erreichbar.',
    },
    {
        value: 'public',
        label: 'Öffentlich',
        description: 'Alle können diesen Garten sehen.',
    },
];

export function GardenForm({
    data,
    errors,
    processing,
    countries,
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
                <Label htmlFor="description">
                    Beschreibung{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <textarea
                    id="description"
                    name="description"
                    rows={3}
                    maxLength={1000}
                    placeholder="Beschreibe deinen Garten …"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                />
                <InputError message={errors.description} />
            </div>

            <div className="grid gap-2">
                <Label>Sichtbarkeit</Label>
                <div className="space-y-2">
                    {VISIBILITY_OPTIONS.map(({ value, label, description }) => (
                        <label
                            key={value}
                            htmlFor={`visibility-${value}`}
                            className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors ${
                                data.visibility === value
                                    ? 'border-primary bg-primary/5'
                                    : 'border-border hover:bg-muted'
                            }`}
                        >
                            <input
                                type="radio"
                                id={`visibility-${value}`}
                                name="visibility"
                                value={value}
                                checked={data.visibility === value}
                                onChange={() => setData('visibility', value)}
                                className="sr-only"
                            />
                            <div>
                                <p className="text-sm font-medium">{label}</p>
                                <p className="text-xs text-muted-foreground">
                                    {description}
                                </p>
                            </div>
                        </label>
                    ))}
                </div>
                <InputError message={errors.visibility} />
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
                    placeholder="z. B. 120.50"
                />
                <InputError message={errors.size_sqm} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="country">
                    Land{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Select
                    value={data.country}
                    onValueChange={(value) => setData('country', value)}
                >
                    <SelectTrigger id="country">
                        <SelectValue placeholder="Land auswählen …" />
                    </SelectTrigger>
                    <SelectContent>
                        {Object.entries(countries).map(([code, name]) => (
                            <SelectItem key={code} value={code}>
                                {name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.country} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="region">
                    Region{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="region"
                    value={data.region}
                    onChange={(e) => setData('region', e.target.value)}
                    placeholder="z. B. Bayern"
                />
                <InputError message={errors.region} />
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
