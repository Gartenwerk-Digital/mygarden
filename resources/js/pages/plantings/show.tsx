import { Head, Link, router } from '@inertiajs/react';
import { Archive, Calendar, MapPin, Pencil, Ruler, Trash2 } from 'lucide-react';

import {
    destroy,
    edit,
    show,
} from '@/actions/App/Http/Controllers/GardenPlantings/GardenPlantingController';
import {
    index as indexGardens,
    show as showGarden,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { PageHeader } from '@/components/mygarden/page-header';
import { PlantingStatusBadge } from '@/components/plantings/planting-status-badge';
import { Button } from '@/components/ui/button';
import type { Garden } from '@/types/garden';
import type { GardenPlanting } from '@/types/planting';
import { formatQuantity } from '@/types/planting';

type PageProps = {
    garden: Garden;
    planting: GardenPlanting;
};

export default function PlantingsShow({ garden, planting }: PageProps) {
    const species = planting.species;
    const variety = planting.variety;
    const area = planting.area;

    const displayName =
        planting.nickname ?? species?.common_name ?? 'Unbekannte Pflanze';
    const quantityLabel = formatQuantity(
        planting.quantity,
        planting.quantity_unit,
    );

    const handleDelete = () => {
        if (
            window.confirm(
                `Möchtest du „${displayName}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.`,
            )
        ) {
            router.delete(destroy({ garden, planting }).url);
        }
    };

    const areaLabel = (() => {
        if (!area) {
            return null;
        }

        if (area.deleted_at) {
            return `${area.name} (Bereich gelöscht)`;
        }

        if (area.archived_at) {
            return `${area.name} (archiviert)`;
        }

        return area.name;
    })();

    return (
        <>
            <Head title={displayName} />

            <div className="p-4 md:p-6">
                <PageHeader
                    title={displayName}
                    actions={
                        <div className="flex items-center gap-2">
                            <Button asChild variant="outline" size="sm">
                                <Link href={edit({ garden, planting }).url}>
                                    <Pencil className="mr-2 size-4" />
                                    Bearbeiten
                                </Link>
                            </Button>
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

                <div className="mt-4 max-w-2xl space-y-6">
                    <div className="flex flex-wrap items-center gap-2">
                        <PlantingStatusBadge status={planting.status} />
                    </div>

                    <div className="space-y-3">
                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Pflanze
                            </p>
                            <p className="mt-1 text-sm">
                                {species ? (
                                    <>
                                        {species.common_name}
                                        {species.archived_at && (
                                            <span className="ml-2 text-xs text-muted-foreground">
                                                (archiviert)
                                            </span>
                                        )}
                                    </>
                                ) : (
                                    <span className="text-muted-foreground">
                                        Unbekannt
                                    </span>
                                )}
                            </p>
                        </div>

                        {variety && (
                            <div>
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Sorte
                                </p>
                                <p className="mt-1 text-sm">
                                    {variety.name}
                                    {variety.archived_at && (
                                        <span className="ml-2 text-xs text-muted-foreground">
                                            (archiviert)
                                        </span>
                                    )}
                                </p>
                            </div>
                        )}

                        {areaLabel && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <MapPin className="size-4 shrink-0" />
                                <span>{areaLabel}</span>
                            </div>
                        )}

                        {quantityLabel && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Ruler className="size-4 shrink-0" />
                                <span>{quantityLabel}</span>
                            </div>
                        )}

                        {planting.planted_at && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Calendar className="size-4 shrink-0" />
                                <span>
                                    Gepflanzt am{' '}
                                    {new Date(
                                        planting.planted_at,
                                    ).toLocaleDateString('de-DE')}
                                </span>
                            </div>
                        )}

                        {planting.acquired_at && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Calendar className="size-4 shrink-0" />
                                <span>
                                    Beschafft am{' '}
                                    {new Date(
                                        planting.acquired_at,
                                    ).toLocaleDateString('de-DE')}
                                </span>
                            </div>
                        )}

                        {planting.status === 'removed' &&
                            planting.removed_at && (
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Archive className="size-4 shrink-0" />
                                    <span>
                                        Entfernt am{' '}
                                        {new Date(
                                            planting.removed_at,
                                        ).toLocaleDateString('de-DE')}
                                    </span>
                                </div>
                            )}
                    </div>

                    {planting.notes && (
                        <div>
                            <p className="mb-1 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Notizen
                            </p>
                            <p className="text-sm leading-relaxed whitespace-pre-wrap text-foreground">
                                {planting.notes}
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

PlantingsShow.layout = (props: {
    garden?: Garden;
    planting?: GardenPlanting;
}) => {
    const displayName =
        props.planting?.nickname ??
        props.planting?.species?.common_name ??
        'Pflanzung';

    return {
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
            ...(props.garden && props.planting
                ? [
                      {
                          title: displayName,
                          href: show({
                              garden: props.garden,
                              planting: props.planting,
                          }).url,
                      },
                  ]
                : []),
        ],
    };
};
