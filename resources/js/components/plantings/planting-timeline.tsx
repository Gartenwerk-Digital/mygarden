import { router } from '@inertiajs/react';
import { BookOpen, Plus } from 'lucide-react';
import { useState } from 'react';

import { create } from '@/actions/App/Http/Controllers/GardenPlantings/PlantingEntryController';
import { destroy } from '@/actions/App/Http/Controllers/GardenPlantings/PlantingEntryController';
import { EmptyState } from '@/components/mygarden/empty-state';
import { Pagination } from '@/components/mygarden/pagination';
import { PlantingEntryCard } from '@/components/plantings/planting-entry-card';
import { Button } from '@/components/ui/button';
import type { Garden } from '@/types/garden';
import type { GardenPlanting } from '@/types/planting';
import type { PlantingEntry } from '@/types/planting-entry';
import type { Paginator } from '@/types/ui';

type Props = {
    garden: Garden;
    planting: GardenPlanting;
    entries: Paginator<PlantingEntry>;
};

export function PlantingTimeline({ garden, planting, entries }: Props) {
    const [deletingId, setDeletingId] = useState<number | null>(null);

    function handleDelete(entry: PlantingEntry) {
        if (deletingId !== null) {
            return;
        }

        if (!confirm('Möchtest du diesen Eintrag wirklich löschen?')) {
            return;
        }

        setDeletingId(entry.id);

        router.delete(destroy({ garden, planting, entry }).url, {
            preserveScroll: true,
            onFinish: () => setDeletingId(null),
        });
    }

    return (
        <section aria-labelledby="timeline-heading">
            <div className="mb-4 flex items-center justify-between gap-4">
                <h2
                    id="timeline-heading"
                    className="text-base font-semibold text-foreground"
                >
                    Timeline
                </h2>
                <Button size="sm" asChild>
                    <a href={create({ garden, planting }).url}>
                        <Plus className="size-4" />
                        Eintrag hinzufügen
                    </a>
                </Button>
            </div>

            {entries.data.length === 0 ? (
                <EmptyState
                    icon={BookOpen}
                    title="Noch keine Einträge"
                    description="Dokumentiere die Entwicklung dieser Pflanzung mit Fotos und Notizen."
                    action={
                        <Button size="sm" asChild>
                            <a href={create({ garden, planting }).url}>
                                <Plus className="size-4" />
                                Ersten Eintrag anlegen
                            </a>
                        </Button>
                    }
                />
            ) : (
                <div className="space-y-3">
                    {entries.data.map((entry) => (
                        <PlantingEntryCard
                            key={entry.id}
                            entry={entry}
                            garden={garden}
                            planting={planting}
                            onDelete={handleDelete}
                        />
                    ))}
                    <Pagination paginator={entries} />
                </div>
            )}
        </section>
    );
}
