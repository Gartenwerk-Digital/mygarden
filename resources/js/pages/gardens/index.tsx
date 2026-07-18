import { Head, Link } from '@inertiajs/react';
import { Plus, Sprout } from 'lucide-react';

import {
    create,
    index,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { GardenCard } from '@/components/gardens/garden-card';
import { EmptyState } from '@/components/mygarden/empty-state';
import { PageHeader } from '@/components/mygarden/page-header';
import { Button } from '@/components/ui/button';
import type { Garden } from '@/types/garden';

type PageProps = {
    activeGardens: Garden[];
    archivedGardens: Garden[];
};

export default function GardensIndex({
    activeGardens,
    archivedGardens,
}: PageProps) {
    const hasAny = activeGardens.length > 0 || archivedGardens.length > 0;

    return (
        <>
            <Head title="Meine Gärten" />

            <div className="p-4 md:p-6">
                <PageHeader
                    title="Meine Gärten"
                    description="Verwalte deine Gärten und behalte den Überblick."
                    actions={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="mr-2 size-4" />
                                Garten anlegen
                            </Link>
                        </Button>
                    }
                />

                {!hasAny ? (
                    <EmptyState
                        icon={Sprout}
                        title="Noch kein Garten vorhanden"
                        description="Leg deinen ersten Garten an und fang an, ihn zu dokumentieren."
                        action={
                            <Button asChild>
                                <Link href={create().url}>
                                    <Plus className="mr-2 size-4" />
                                    Ersten Garten anlegen
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <div className="space-y-10">
                        {activeGardens.length > 0 ? (
                            <section>
                                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {activeGardens.map((garden) => (
                                        <GardenCard
                                            key={garden.id}
                                            garden={garden}
                                        />
                                    ))}
                                </div>
                            </section>
                        ) : (
                            <EmptyState
                                icon={Sprout}
                                title="Keine aktiven Gärten"
                                description="Alle Gärten sind archiviert."
                                action={
                                    <Button asChild variant="outline">
                                        <Link href={create().url}>
                                            <Plus className="mr-2 size-4" />
                                            Garten anlegen
                                        </Link>
                                    </Button>
                                }
                            />
                        )}

                        {archivedGardens.length > 0 && (
                            <section>
                                <h2 className="mb-4 text-sm font-medium text-muted-foreground">
                                    Archiviert
                                </h2>
                                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {archivedGardens.map((garden) => (
                                        <GardenCard
                                            key={garden.id}
                                            garden={garden}
                                        />
                                    ))}
                                </div>
                            </section>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

GardensIndex.layout = () => ({
    breadcrumbs: [{ title: 'Meine Gärten', href: index().url }],
});
