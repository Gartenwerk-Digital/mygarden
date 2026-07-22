import { useEffect, useRef, useState } from 'react';
import type { FormEventHandler, KeyboardEvent } from 'react';

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
import type {
    PlantingFormData,
    PlantingStatus,
    PlantingUnit,
    PlantSpeciesOption,
    PlantVarietyOption,
} from '@/types/planting';

type AreaOption = {
    id: number;
    name: string;
    full_label: string;
};

type EnumOption = {
    value: string;
    label: string;
};

type Props = {
    data: PlantingFormData;
    errors: Partial<Record<keyof PlantingFormData | string, string>>;
    processing: boolean;
    availableSpecies: PlantSpeciesOption[];
    areas: AreaOption[];
    statuses: EnumOption[];
    units: EnumOption[];
    submitLabel: string;
    cancelHref: string;
    setData: <K extends keyof PlantingFormData>(
        key: K,
        value: PlantingFormData[K],
    ) => void;
    onSubmit: FormEventHandler;
};

const MAX_SEARCH_RESULTS = 20;

export function PlantingForm({
    data,
    errors,
    processing,
    availableSpecies,
    areas,
    statuses,
    units,
    submitLabel,
    cancelHref,
    setData,
    onSubmit,
}: Props) {
    const [query, setQuery] = useState('');
    const [isSearchOpen, setIsSearchOpen] = useState(false);
    const [focusedIndex, setFocusedIndex] = useState(-1);
    const searchRef = useRef<HTMLInputElement>(null);
    const listRef = useRef<HTMLUListElement>(null);

    const selectedSpecies = availableSpecies.find(
        (s) => s.id === data.plant_species_id,
    );

    const filtered = selectedSpecies
        ? []
        : availableSpecies
              .filter((s) => {
                  const q = query.toLowerCase();

                  return (
                      s.common_name.toLowerCase().includes(q) ||
                      (s.botanical_name ?? '').toLowerCase().includes(q) ||
                      (s.alternate_name ?? '').toLowerCase().includes(q)
                  );
              })
              .slice(0, MAX_SEARCH_RESULTS);

    const availableVarieties: PlantVarietyOption[] =
        selectedSpecies?.varieties ?? [];

    function selectSpecies(species: PlantSpeciesOption) {
        setData('plant_species_id', species.id);
        setData('plant_variety_id', '');
        setQuery('');
        setIsSearchOpen(false);
        setFocusedIndex(-1);
    }

    function clearSpecies() {
        setData('plant_species_id', '');
        setData('plant_variety_id', '');
        setQuery('');
        setIsSearchOpen(false);
        setTimeout(() => searchRef.current?.focus(), 0);
    }

    function handleSearchKeyDown(e: KeyboardEvent<HTMLInputElement>) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setFocusedIndex((i) => Math.min(i + 1, filtered.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setFocusedIndex((i) => Math.max(i - 1, 0));
        } else if (
            e.key === 'Enter' &&
            focusedIndex >= 0 &&
            filtered[focusedIndex]
        ) {
            e.preventDefault();
            selectSpecies(filtered[focusedIndex]);
        } else if (e.key === 'Escape') {
            setIsSearchOpen(false);
            setFocusedIndex(-1);
        }
    }

    useEffect(() => {
        if (focusedIndex >= 0 && listRef.current) {
            const item = listRef.current.children[focusedIndex] as HTMLElement;
            item?.scrollIntoView({ block: 'nearest' });
        }
    }, [focusedIndex]);

    const isPieces = data.quantity_unit === 'pieces';

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            {/* Pflanzensuche */}
            <div className="grid gap-2">
                <Label htmlFor="species-search">Pflanze</Label>

                {selectedSpecies ? (
                    <div className="flex items-center gap-2 rounded-md border border-input bg-muted/40 px-3 py-2">
                        <span className="flex-1 text-sm">
                            {selectedSpecies.common_name}
                            {selectedSpecies.botanical_name && (
                                <span className="ml-1 text-muted-foreground">
                                    ({selectedSpecies.botanical_name})
                                </span>
                            )}
                        </span>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="h-auto px-2 py-0.5 text-xs"
                            onClick={clearSpecies}
                        >
                            Ändern
                        </Button>
                    </div>
                ) : (
                    <div className="relative">
                        <Input
                            id="species-search"
                            ref={searchRef}
                            type="search"
                            placeholder="Pflanze suchen …"
                            value={query}
                            autoComplete="off"
                            onChange={(e) => {
                                setQuery(e.target.value);
                                setIsSearchOpen(true);
                                setFocusedIndex(-1);
                            }}
                            onFocus={() => setIsSearchOpen(true)}
                            onBlur={() =>
                                setTimeout(() => setIsSearchOpen(false), 150)
                            }
                            onKeyDown={handleSearchKeyDown}
                        />
                        {isSearchOpen && query.length > 0 && (
                            <ul
                                ref={listRef}
                                className="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border border-border bg-popover shadow-md"
                                role="listbox"
                            >
                                {filtered.length === 0 ? (
                                    <li className="px-3 py-2 text-sm text-muted-foreground">
                                        Keine Treffer
                                    </li>
                                ) : (
                                    filtered.map((species, i) => (
                                        <li
                                            key={species.id}
                                            role="option"
                                            aria-selected={i === focusedIndex}
                                            className={`cursor-pointer px-3 py-2 text-sm ${
                                                i === focusedIndex
                                                    ? 'bg-accent text-accent-foreground'
                                                    : 'hover:bg-muted'
                                            }`}
                                            onMouseDown={() =>
                                                selectSpecies(species)
                                            }
                                        >
                                            <span className="font-medium">
                                                {species.common_name}
                                            </span>
                                            {species.botanical_name && (
                                                <span className="ml-1 text-muted-foreground">
                                                    ({species.botanical_name})
                                                </span>
                                            )}
                                        </li>
                                    ))
                                )}
                            </ul>
                        )}
                    </div>
                )}
                <InputError message={errors.plant_species_id} />
            </div>

            {/* Sorte */}
            {selectedSpecies && availableVarieties.length > 0 && (
                <div className="grid gap-2">
                    <Label htmlFor="plant_variety_id">
                        Sorte{' '}
                        <span className="text-muted-foreground">
                            (optional)
                        </span>
                    </Label>
                    <Select
                        value={
                            data.plant_variety_id !== ''
                                ? String(data.plant_variety_id)
                                : ''
                        }
                        onValueChange={(value) =>
                            setData(
                                'plant_variety_id',
                                value !== '' ? (Number(value) as number) : '',
                            )
                        }
                    >
                        <SelectTrigger id="plant_variety_id">
                            <SelectValue placeholder="Keine Sorte" />
                        </SelectTrigger>
                        <SelectContent>
                            {availableVarieties.map((v) => (
                                <SelectItem key={v.id} value={String(v.id)}>
                                    {v.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.plant_variety_id} />
                </div>
            )}

            {/* Bereich */}
            <div className="grid gap-2">
                <Label htmlFor="area_id">
                    Bereich{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Select
                    value={data.area_id !== '' ? String(data.area_id) : ''}
                    onValueChange={(value) =>
                        setData(
                            'area_id',
                            value !== '' ? (Number(value) as number) : '',
                        )
                    }
                >
                    <SelectTrigger id="area_id">
                        <SelectValue placeholder="Nicht zugeordnet" />
                    </SelectTrigger>
                    <SelectContent>
                        {areas.map((area) => (
                            <SelectItem key={area.id} value={String(area.id)}>
                                {area.full_label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.area_id} />
            </div>

            {/* Status */}
            <div className="grid gap-2">
                <Label htmlFor="status">Status</Label>
                <Select
                    value={data.status}
                    onValueChange={(value) =>
                        setData('status', value as PlantingStatus)
                    }
                >
                    <SelectTrigger id="status">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {statuses.map((s) => (
                            <SelectItem key={s.value} value={s.value}>
                                {s.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.status} />
            </div>

            {/* Menge und Einheit */}
            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="quantity_unit">
                        Einheit{' '}
                        <span className="text-muted-foreground">
                            (optional)
                        </span>
                    </Label>
                    <Select
                        value={data.quantity_unit}
                        onValueChange={(value) => {
                            setData(
                                'quantity_unit',
                                value as PlantingUnit | '',
                            );
                            setData('quantity', '');
                        }}
                    >
                        <SelectTrigger id="quantity_unit">
                            <SelectValue placeholder="Keine Angabe" />
                        </SelectTrigger>
                        <SelectContent>
                            {units.map((u) => (
                                <SelectItem key={u.value} value={u.value}>
                                    {u.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.quantity_unit} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="quantity">
                        Menge{' '}
                        <span className="text-muted-foreground">
                            (optional)
                        </span>
                    </Label>
                    <Input
                        id="quantity"
                        type="number"
                        min={isPieces ? '1' : '0.01'}
                        max={isPieces ? '99999999' : '99999999.99'}
                        step={isPieces ? '1' : '0.01'}
                        value={data.quantity}
                        onChange={(e) => setData('quantity', e.target.value)}
                        disabled={data.quantity_unit === ''}
                        placeholder={isPieces ? 'z. B. 3' : 'z. B. 12.50'}
                    />
                    <InputError message={errors.quantity} />
                </div>
            </div>

            {/* Spitzname */}
            <div className="grid gap-2">
                <Label htmlFor="nickname">
                    Spitzname{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="nickname"
                    value={data.nickname}
                    onChange={(e) => setData('nickname', e.target.value)}
                    maxLength={100}
                    placeholder="z. B. Elstar am Zaun"
                />
                <InputError message={errors.nickname} />
            </div>

            {/* Pflanzdatum */}
            <div className="grid gap-2">
                <Label htmlFor="planted_at">
                    Pflanzdatum{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="planted_at"
                    type="date"
                    value={data.planted_at}
                    onChange={(e) => setData('planted_at', e.target.value)}
                />
                <InputError message={errors.planted_at} />
            </div>

            {/* Beschaffungsdatum */}
            <div className="grid gap-2">
                <Label htmlFor="acquired_at">
                    Beschaffungsdatum{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="acquired_at"
                    type="date"
                    value={data.acquired_at}
                    onChange={(e) => setData('acquired_at', e.target.value)}
                />
                <InputError message={errors.acquired_at} />
            </div>

            {/* Notizen */}
            <div className="grid gap-2">
                <Label htmlFor="notes">
                    Notizen{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <textarea
                    id="notes"
                    rows={4}
                    maxLength={5000}
                    value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)}
                    placeholder="Beobachtungen, Pflegehinweise …"
                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                />
                <InputError message={errors.notes} />
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
