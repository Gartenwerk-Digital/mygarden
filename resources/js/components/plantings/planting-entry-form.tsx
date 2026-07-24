import type { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { EntryImageGallery } from '@/components/plantings/entry-image-gallery';
import { EntryImageUpload } from '@/components/plantings/entry-image-upload';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Garden } from '@/types/garden';
import type { GardenPlanting } from '@/types/planting';
import type {
    EntryType,
    EntryTypeOption,
    PlantingEntry,
    PlantingEntryFormData,
} from '@/types/planting-entry';

type Props = {
    data: PlantingEntryFormData;
    errors: Partial<Record<keyof PlantingEntryFormData | string, string>>;
    processing: boolean;
    entryTypes: EntryTypeOption[];
    garden: Garden;
    planting: GardenPlanting;
    existingEntry?: PlantingEntry;
    submitLabel: string;
    cancelHref: string;
    setData: <K extends keyof PlantingEntryFormData>(
        key: K,
        value: PlantingEntryFormData[K],
    ) => void;
    onSubmit: FormEventHandler;
};

const MAX_TOTAL_IMAGES = 10;

export function PlantingEntryForm({
    data,
    errors,
    processing,
    entryTypes,
    garden,
    planting,
    existingEntry,
    submitLabel,
    cancelHref,
    setData,
    onSubmit,
}: Props) {
    const existingCount = existingEntry
        ? existingEntry.images.filter(
              (img) => !data.remove_image_ids.includes(img.id),
          ).length
        : 0;
    const maxNewImages = Math.min(5, MAX_TOTAL_IMAGES - existingCount);

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div className="space-y-2">
                <Label htmlFor="entry_type">Typ</Label>
                <Select
                    value={data.entry_type}
                    onValueChange={(v) => setData('entry_type', v as EntryType)}
                    disabled={processing}
                >
                    <SelectTrigger id="entry_type">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {entryTypes.map((t) => (
                            <SelectItem key={t.value} value={t.value}>
                                {t.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.entry_type} />
            </div>

            <div className="space-y-2">
                <Label htmlFor="occurred_at">Datum</Label>
                <input
                    id="occurred_at"
                    type="date"
                    value={data.occurred_at}
                    onChange={(e) => setData('occurred_at', e.target.value)}
                    disabled={processing}
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                />
                <InputError message={errors.occurred_at} />
            </div>

            <div className="space-y-2">
                <Label htmlFor="notes">Notiz</Label>
                <textarea
                    id="notes"
                    rows={4}
                    maxLength={5000}
                    value={data.notes}
                    onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) =>
                        setData('notes', e.target.value)
                    }
                    disabled={processing}
                    placeholder="Was hast du beobachtet?"
                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                />
                <InputError message={errors.notes} />
            </div>

            <div className="space-y-2">
                <Label>Bilder</Label>

                {existingEntry && existingEntry.images.length > 0 && (
                    <div className="space-y-2">
                        <p className="text-sm text-muted-foreground">
                            Vorhandene Bilder
                        </p>
                        <EntryImageGallery
                            garden={garden}
                            planting={planting}
                            entry={existingEntry}
                            markedForRemoval={data.remove_image_ids}
                            onToggleRemoval={(id) => {
                                const current = data.remove_image_ids;
                                setData(
                                    'remove_image_ids',
                                    current.includes(id)
                                        ? current.filter((x) => x !== id)
                                        : [...current, id],
                                );
                            }}
                            disabled={processing}
                        />
                    </div>
                )}

                {maxNewImages > 0 && (
                    <EntryImageUpload
                        files={data.images}
                        onChange={(files) => setData('images', files)}
                        maxFiles={maxNewImages}
                        disabled={processing}
                    />
                )}

                <p className="text-xs text-muted-foreground">
                    insgesamt max. {MAX_TOTAL_IMAGES} Bilder pro Eintrag
                </p>
                <InputError message={errors['images']} />
                <InputError message={errors['images.0']} />
            </div>

            <div className="flex gap-3">
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>
                <Button variant="outline" asChild>
                    <a href={cancelHref}>Abbrechen</a>
                </Button>
            </div>
        </form>
    );
}
