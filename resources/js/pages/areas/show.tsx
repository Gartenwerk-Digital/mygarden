import { Head, Link, router } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    Droplets,
    Layers,
    Pencil,
    Plus,
    Ruler,
    Sprout,
    Sun,
    Trash2,
} from 'lucide-react';

import {
    archive,
    destroy,
    edit,
    show,
    unarchive,
    create as createArea,
} from '@/actions/App/Http/Controllers/Areas/AreaController';
import {
    index as indexGardens,
    show as showGarden,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { AreaCard } from '@/components/areas/area-card';
import { EmptyState } from '@/components/mygarden/empty-state';
import { PageHeader } from '@/components/mygarden/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    areaTypeLabels,
    moistureLevelLabels,
    soilTypeLabels,
    sunExposureLabels,
} from '@/lib/area-type-icons';
import type { Area, Garden } from '@/types';

type PageProps = {
    garden: Garden;
    area: Area;
};

export default function AreasShow({ garden, area }: PageProps) {
    const isRoot = area.parent_id === null;
    const children = area.children ?? [];
    const archivedChildren = area.archivedChildren ?? [];

    const handleArchive = () => {
        router.patch(archive({ garden, area }).url);
    };

    const handleUnarchive = () => {
        router.patch(unarchive({ garden, area }).url);
    };

    const handleDelete = () => {
        if (
            window.confirm(
                `Möchtest du „${area.name}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.`,
            )
        ) {
            router.delete(destroy({ garden, area }).url);
        }
    };

    return (
        <>
            <Head title={area.name} />

            <div className="p-4 md:p-6">
                <PageHeader
                    title={area.name}
                    actions={
                        <div className="flex items-center gap-2">
                            <Button asChild variant="outline" size="sm">
                                <Link href={edit({ garden, area }).url}>
                                    <Pencil className="mr-2 size-4" />
                                    Bearbeiten
                                </Link>
                            </Button>
                            {area.archived_at ? (
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

                {area.archived_at && (
                    <div className="mb-6 flex items-center gap-2 rounded-lg border border-border bg-muted/50 px-4 py-3 text-sm text-muted-foreground">
                        <Archive className="size-4 shrink-0" />
                        <span>Dieser Bereich ist archiviert.</span>
                    </div>
                )}

                <div className="max-w-2xl space-y-6">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="secondary">
                            {areaTypeLabels[area.type]}
                        </Badge>
                        {area.sun_exposure && (
                            <Badge variant="outline" className="gap-1">
                                <Sun className="size-3" />
                                {sunExposureLabels[area.sun_exposure]}
                            </Badge>
                        )}
                        {area.soil_type && (
                            <Badge variant="outline" className="gap-1">
                                <Layers className="size-3" />
                                {soilTypeLabels[area.soil_type]}
                            </Badge>
                        )}
                        {area.moisture_level && (
                            <Badge variant="outline" className="gap-1">
                                <Droplets className="size-3" />
                                {moistureLevelLabels[area.moisture_level]}
                            </Badge>
                        )}
                    </div>

                    {area.description && (
                        <p className="text-sm leading-relaxed text-foreground">
                            {area.description}
                        </p>
                    )}

                    {area.size_sqm && (
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Ruler className="size-4 shrink-0" />
                            <span>
                                {parseFloat(area.size_sqm).toLocaleString(
                                    'de-DE',
                                )}{' '}
                                m²
                            </span>
                        </div>
                    )}

                    {area.parent && (
                        <div>
                            <p className="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Übergeordneter Bereich
                            </p>
                            <Link
                                href={show({ garden, area: area.parent }).url}
                                className="rounded-sm text-sm font-medium hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                {area.parent.name}
                            </Link>
                        </div>
                    )}
                </div>

                {isRoot && (
                    <div className="mt-10">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="text-lg font-semibold">
                                Unterbereiche
                            </h2>
                            <Button asChild size="sm">
                                <Link
                                    href={`${createArea(garden).url}?parent=${area.id}`}
                                >
                                    <Plus className="mr-2 size-4" />
                                    Unterbereich hinzufügen
                                </Link>
                            </Button>
                        </div>

                        {children.length === 0 &&
                        archivedChildren.length === 0 ? (
                            <EmptyState
                                icon={Sprout}
                                title="Keine Unterbereiche"
                                description="Füge einen Unterbereich hinzu, um diesen Bereich weiter zu unterteilen."
                                className="py-10"
                            />
                        ) : (
                            <div className="space-y-4">
                                {children.length > 0 && (
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        {children.map((child) => (
                                            <AreaCard
                                                key={child.id}
                                                area={child}
                                                garden={garden}
                                            />
                                        ))}
                                    </div>
                                )}

                                {archivedChildren.length > 0 && (
                                    <div>
                                        <p className="mb-3 text-sm font-medium text-muted-foreground">
                                            Archiviert
                                        </p>
                                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            {archivedChildren.map((child) => (
                                                <AreaCard
                                                    key={child.id}
                                                    area={child}
                                                    garden={garden}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

AreasShow.layout = (props: { garden?: Garden; area?: Area }) => ({
    breadcrumbs: [
        { title: 'Meine Gärten', href: indexGardens().url },
        ...(props.garden
            ? [
                  {
                      title: props.garden.name,
                      href: showGarden(props.garden).url,
                  },
              ]
            : []),
        ...(props.garden && props.area
            ? [
                  {
                      title: props.area.name,
                      href: show({ garden: props.garden, area: props.area })
                          .url,
                  },
              ]
            : []),
    ],
});
