import { X } from 'lucide-react';

import { show } from '@/actions/App/Http/Controllers/GardenPlantings/PlantingEntryImageController';
import { ImagePlaceholder } from '@/components/mygarden/image-placeholder';
import type { Garden } from '@/types/garden';
import type { GardenPlanting } from '@/types/planting';
import type { PlantingEntry, PlantingEntryImage } from '@/types/planting-entry';

type Props = {
    garden: Garden;
    planting: GardenPlanting;
    entry: PlantingEntry;
    markedForRemoval: number[];
    onToggleRemoval: (id: number) => void;
    disabled?: boolean;
};

export function EntryImageGallery({
    garden,
    planting,
    entry,
    markedForRemoval,
    onToggleRemoval,
    disabled = false,
}: Props) {
    if (entry.images.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2">
            {entry.images.map((image: PlantingEntryImage) => {
                const isMarked = markedForRemoval.includes(image.id);
                const imageUrl = show({
                    garden: garden.id,
                    planting: planting.id,
                    entry: entry.id,
                    image: image.id,
                }).url;

                return (
                    <div key={image.id} className="group relative">
                        {isMarked ? (
                            <ImagePlaceholder
                                className="h-20 w-20"
                                label="Wird entfernt"
                            />
                        ) : (
                            <img
                                src={imageUrl}
                                alt=""
                                className="h-20 w-20 rounded-md border border-border object-cover"
                            />
                        )}
                        <button
                            type="button"
                            onClick={() => onToggleRemoval(image.id)}
                            disabled={disabled}
                            className={`absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full transition-opacity focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none ${
                                isMarked
                                    ? 'bg-muted text-muted-foreground opacity-100'
                                    : 'bg-destructive text-destructive-foreground opacity-0 group-hover:opacity-100 focus-visible:opacity-100'
                            }`}
                            aria-label={
                                isMarked
                                    ? 'Markierung aufheben'
                                    : 'Bild entfernen'
                            }
                        >
                            <X className="size-3" />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}
