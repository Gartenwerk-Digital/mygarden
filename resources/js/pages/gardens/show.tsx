import { Head, Link, router } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    MapPin,
    Pencil,
    Plus,
    Ruler,
    Sprout,
    Trash2,
} from 'lucide-react';

import { create as createArea } from '@/actions/App/Http/Controllers/Areas/AreaController';
import {
    archive,
    destroy,
    edit,
    index,
    show,
    unarchive,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { AreaCard } from '@/components/areas/area-card';
import { EmptyState } from '@/components/mygarden/empty-state';
import { PageHeader } from '@/components/mygarden/page-header';
import { VisibilityBadge } from '@/components/mygarden/visibility-badge';
import { Button } from '@/components/ui/button';
import type { Area, Garden } from '@/types';

type PageProps = {
    garden: Garden;
    activeAreas: Area[];
    archivedAreas: Area[];
};

export default function GardensShow({
    garden,
    activeAreas,
    archivedAreas,
}: PageProps) {
    const handleArchive = () => {
        router.patch(archive(garden).url);
    };

    const handleUnarchive = () => {
        router.patch(unarchive(garden).url);
    };

    const handleDelete = () => {
        if (
            window.confirm(
                `Möchtest du „${garden.name}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.`,
            )
        ) {
            router.delete(destroy(garden).url);
        }
    };

    return (
        <>
            <Head title={garden.name} />

            <div className="p-4 md:p-6">
                <PageHeader
                    title={garden.name}
                    actions={
                        <div className="flex items-center gap-2">
                            <Button asChild variant="outline" size="sm">
                                <Link href={edit(garden).url}>
                                    <Pencil className="mr-2 size-4" />
                                    Bearbeiten
                                </Link>
                            </Button>
                            {garden.archived_at ? (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handleUnarchive}
                                >
                                    <ArchiveRestore className="mr-2 size-4" />
                                    Aktivieren
                                </Button>
                            ) : (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handleArchive}
                                >
                                    <Archive className="mr-2 size-4" />
                                    Archivieren
                                </Button>
                            )}
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={handleDelete}
                            >
                                <Trash2 className="mr-2 size-4" />
                                Löschen
                            </Button>
                        </div>
                    }
                />

                {garden.archived_at && (
                    <div className="mb-6 flex items-center gap-2 rounded-lg border border-border bg-muted/50 px-4 py-3 text-sm text-muted-foreground">
                        <Archive className="size-4 shrink-0" />
                        <span>
                            Dieser Garten ist archiviert und nicht öffentlich
                            sichtbar.
                        </span>
                    </div>
                )}

                <div className="max-w-2xl space-y-6">
                    <div className="flex items-center gap-3">
                        <VisibilityBadge visibility={garden.visibility} />
                        {(garden.visibility === 'public' ||
                            garden.visibility === 'connections') && (
                            <p className="text-xs text-muted-foreground">
                                Community-Freigaben werden in einer zukünftigen
                                Version aktiviert.
                            </p>
                        )}
                    </div>

                    {garden.description && (
                        <div>
                            <p className="text-sm leading-relaxed text-foreground">
                                {garden.description}
                            </p>
                        </div>
                    )}

                    <div className="space-y-2">
                        {garden.size_sqm && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Ruler className="size-4 shrink-0" />
                                <span>
                                    {parseFloat(garden.size_sqm).toLocaleString(
                                        'de-DE',
                                    )}{' '}
                                    m²
                                </span>
                            </div>
                        )}
                        {(garden.region || garden.country) && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <MapPin className="size-4 shrink-0" />
                                <span>
                                    {[garden.region, garden.country]
                                        .filter(Boolean)
                                        .join(', ')}
                                </span>
                            </div>
                        )}
                    </div>
                </div>

                <div className="mt-10">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Bereiche</h2>
                        <Button asChild size="sm">
                            <Link href={createArea(garden).url}>
                                <Plus className="mr-2 size-4" />
                                Bereich hinzufügen
                            </Link>
                        </Button>
                    </div>

                    {activeAreas.length === 0 && archivedAreas.length === 0 ? (
                        <EmptyState
                            icon={Sprout}
                            title="Noch keine Bereiche"
                            description="Unterteile deinen Garten in Bereiche wie Beete, Rasen oder Terrasse."
                            action={
                                <Button asChild size="sm">
                                    <Link href={createArea(garden).url}>
                                        <Plus className="mr-2 size-4" />
                                        Ersten Bereich erstellen
                                    </Link>
                                </Button>
                            }
                        />
                    ) : (
                        <div className="space-y-6">
                            {activeAreas.length > 0 && (
                                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {activeAreas.map((area) => (
                                        <AreaCard
                                            key={area.id}
                                            area={area}
                                            garden={garden}
                                        />
                                    ))}
                                </div>
                            )}

                            {archivedAreas.length > 0 && (
                                <div>
                                    <p className="mb-3 text-sm font-medium text-muted-foreground">
                                        Archiviert
                                    </p>
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        {archivedAreas.map((area) => (
                                            <AreaCard
                                                key={area.id}
                                                area={area}
                                                garden={garden}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

GardensShow.layout = (props: { garden?: Garden }) => ({
    breadcrumbs: [
        { title: 'Meine Gärten', href: index().url },
        ...(props.garden
            ? [{ title: props.garden.name, href: show(props.garden).url }]
            : []),
    ],
});
