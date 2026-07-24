import { Link } from '@inertiajs/react';
import { Image, Pencil, Trash2 } from 'lucide-react';

import { edit } from '@/actions/App/Http/Controllers/GardenPlantings/PlantingEntryController';
import { show as showImage } from '@/actions/App/Http/Controllers/GardenPlantings/PlantingEntryImageController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Garden } from '@/types/garden';
import type { GardenPlanting } from '@/types/planting';
import type { PlantingEntry } from '@/types/planting-entry';
import { entryTypeLabels } from '@/types/planting-entry';

type Props = {
    entry: PlantingEntry;
    garden: Garden;
    planting: GardenPlanting;
    onDelete: (entry: PlantingEntry) => void;
};

export function PlantingEntryCard({
    entry,
    garden,
    planting,
    onDelete,
}: Props) {
    const date = new Date(entry.occurred_at).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });

    return (
        <div className="rounded-lg border border-border bg-card p-4 shadow-sm">
            <div className="mb-3 flex items-start justify-between gap-3">
                <div className="flex items-center gap-2">
                    <time
                        dateTime={entry.occurred_at}
                        className="text-sm font-medium text-foreground"
                    >
                        {date}
                    </time>
                    <Badge variant="secondary" className="text-xs">
                        {entryTypeLabels[entry.entry_type]}
                    </Badge>
                </div>
                <div className="flex shrink-0 gap-1">
                    <Button variant="ghost" size="icon" asChild>
                        <Link
                            href={edit({ garden, planting, entry }).url}
                            aria-label="Eintrag bearbeiten"
                        >
                            <Pencil className="size-4" />
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onDelete(entry)}
                        aria-label="Eintrag löschen"
                    >
                        <Trash2 className="size-4 text-destructive" />
                    </Button>
                </div>
            </div>

            {entry.notes && (
                <p className="mb-3 text-sm text-foreground">{entry.notes}</p>
            )}

            {entry.images.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    {entry.images.map((image) => (
                        <a
                            key={image.id}
                            href={
                                showImage({
                                    garden: garden.id,
                                    planting: planting.id,
                                    entry: entry.id,
                                    image: image.id,
                                }).url
                            }
                            target="_blank"
                            rel="noreferrer"
                            className="block focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1 focus-visible:outline-none"
                        >
                            <img
                                src={
                                    showImage({
                                        garden: garden.id,
                                        planting: planting.id,
                                        entry: entry.id,
                                        image: image.id,
                                    }).url
                                }
                                alt=""
                                className="h-20 w-20 rounded-md border border-border object-cover"
                            />
                        </a>
                    ))}
                </div>
            )}

            {entry.images.length === 0 && !entry.notes && (
                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    <Image className="size-3" />
                    <span>Kein Inhalt</span>
                </div>
            )}
        </div>
    );
}
