import type { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export type PlantVarietyFormData = {
    name: string;
    breeder: string;
    year_introduced: string;
    description: string;
};

type Props = {
    data: PlantVarietyFormData;
    errors: Partial<Record<keyof PlantVarietyFormData, string>>;
    processing: boolean;
    setData: <K extends keyof PlantVarietyFormData>(
        key: K,
        value: PlantVarietyFormData[K],
    ) => void;
    onSubmit: FormEventHandler;
    submitLabel: string;
    cancelHref: string;
};

export function PlantVarietyForm({
    data,
    errors,
    processing,
    setData,
    onSubmit,
    submitLabel,
    cancelHref,
}: Props) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div className="grid gap-2">
                <Label htmlFor="name">Sortenname</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    maxLength={150}
                    required
                    autoFocus
                    placeholder="z. B. Hidcote"
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="breeder">
                    Züchter{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="breeder"
                    value={data.breeder}
                    onChange={(e) => setData('breeder', e.target.value)}
                    maxLength={150}
                />
                <InputError message={errors.breeder} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="year_introduced">
                    Einführungsjahr{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="year_introduced"
                    type="number"
                    min="1500"
                    max={new Date().getFullYear()}
                    step="1"
                    value={data.year_introduced}
                    onChange={(e) => setData('year_introduced', e.target.value)}
                    placeholder="z. B. 1950"
                />
                <InputError message={errors.year_introduced} />
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
