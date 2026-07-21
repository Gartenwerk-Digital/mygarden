import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    Droplets,
    Layers,
    Pencil,
    Plus,
    Ruler,
    Send,
    Sun,
    Trash2,
    Undo2,
} from 'lucide-react';

import { showSpecies as moderationShow } from '@/actions/App/Http/Controllers/Plants/PlantModerationController';
import {
    archive,
    destroy,
    edit,
    index,
    show,
    submit,
    unarchive,
    withdraw,
} from '@/actions/App/Http/Controllers/Plants/PlantSpeciesController';
import {
    archive as archiveVariety,
    create as createVariety,
    destroy as destroyVariety,
    edit as editVariety,
    unarchive as unarchiveVariety,
} from '@/actions/App/Http/Controllers/Plants/PlantVarietyController';
import { EmptyState } from '@/components/mygarden/empty-state';
import { PageHeader } from '@/components/mygarden/page-header';
import { CatalogStatusBadge } from '@/components/plants/catalog-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Auth } from '@/types/auth';
import type { EnumOption, PlantSpecies, PlantVariety } from '@/types/plant';

type PageProps = {
    plantSpecies: PlantSpecies;
    archivedVarieties: PlantVariety[];
    categories: EnumOption[];
    lifecycles: EnumOption[];
    sunExposures: EnumOption[];
    soilTypes: EnumOption[];
    moistureLevels: EnumOption[];
};

export default function PlantsShow({
    plantSpecies,
    archivedVarieties,
    categories,
    lifecycles,
    sunExposures,
    soilTypes,
    moistureLevels,
}: PageProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const userId = auth.user.id;
    const canModerateCatalog = auth.canModerateCatalog;

    const isOwner =
        plantSpecies.origin === 'user' && plantSpecies.created_by === userId;
    const isEditableStatus =
        plantSpecies.catalog_status === 'private' ||
        plantSpecies.catalog_status === 'rejected';
    const canEdit = isOwner && isEditableStatus;
    const canSubmit = isOwner && isEditableStatus && !plantSpecies.archived_at;
    const canWithdraw =
        isOwner && plantSpecies.catalog_status === 'pending_review';
    const varieties = plantSpecies.varieties ?? [];

    const labelFor = (
        options: EnumOption[],
        value: string | null | undefined,
    ) =>
        value ? (options.find((o) => o.value === value)?.label ?? value) : null;

    const handleArchive = () => router.patch(archive(plantSpecies).url);
    const handleUnarchive = () => router.patch(unarchive(plantSpecies).url);
    const handleSubmit = () => router.patch(submit(plantSpecies).url);
    const handleWithdraw = () => {
        if (
            window.confirm(
                'Einreichung zurückziehen? Der Eintrag wird wieder privat.',
            )
        ) {
            router.patch(withdraw(plantSpecies).url);
        }
    };
    const handleDelete = () => {
        if (
            window.confirm(
                `Möchtest du „${plantSpecies.common_name}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.`,
            )
        ) {
            router.delete(destroy(plantSpecies).url);
        }
    };

    const handleDeleteVariety = (variety: PlantVariety) => {
        if (window.confirm(`Sorte „${variety.name}" wirklich löschen?`)) {
            router.delete(destroyVariety({ plantSpecies, variety }).url);
        }
    };

    return (
        <>
            <Head title={plantSpecies.common_name} />

            <div className="p-4 md:p-6">
                <PageHeader
                    title={plantSpecies.common_name}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            {canEdit && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={edit(plantSpecies).url}>
                                        <Pencil className="mr-2 size-4" />
                                        Bearbeiten
                                    </Link>
                                </Button>
                            )}
                            {canEdit &&
                                (plantSpecies.archived_at ? (
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
                                ))}
                            {canSubmit && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handleSubmit}
                                >
                                    <Send className="mr-2 size-4" />
                                    Zur Prüfung einreichen
                                </Button>
                            )}
                            {canWithdraw && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handleWithdraw}
                                >
                                    <Undo2 className="mr-2 size-4" />
                                    Einreichung zurückziehen
                                </Button>
                            )}
                            {canEdit && (
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    onClick={handleDelete}
                                >
                                    <Trash2 className="mr-2 size-4" />
                                    Löschen
                                </Button>
                            )}
                        </div>
                    }
                />

                {plantSpecies.archived_at && (
                    <div className="mb-6 flex items-center gap-2 rounded-lg border border-border bg-muted/50 px-4 py-3 text-sm text-muted-foreground">
                        <Archive className="size-4 shrink-0" />
                        <span>Diese Pflanzenart ist archiviert.</span>
                    </div>
                )}

                {isOwner && plantSpecies.catalog_status === 'rejected' && (
                    <div className="mb-6 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm">
                        <p className="font-medium text-destructive">
                            Eintrag abgelehnt
                        </p>
                        {plantSpecies.moderation_note && (
                            <p className="mt-1 text-foreground">
                                {plantSpecies.moderation_note}
                            </p>
                        )}
                        {plantSpecies.duplicate_of && (
                            <p className="mt-1 text-muted-foreground">
                                Ähnlicher Eintrag:{' '}
                                <Link
                                    href={show(plantSpecies.duplicate_of).url}
                                    className="underline"
                                >
                                    {plantSpecies.duplicate_of.common_name}
                                </Link>
                            </p>
                        )}
                    </div>
                )}

                {canModerateCatalog &&
                    plantSpecies.catalog_status === 'pending_review' && (
                        <div className="mb-6 flex items-center gap-2 rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground">
                            <span>Dieser Eintrag wartet auf Prüfung.</span>
                            <Link
                                href={moderationShow(plantSpecies).url}
                                className="ml-auto shrink-0 underline"
                            >
                                Zur Moderationsdetailseite
                            </Link>
                        </div>
                    )}

                {isOwner && plantSpecies.catalog_status === 'approved' && (
                    <div className="mb-6 flex items-center gap-2 rounded-lg border border-border bg-muted/50 px-4 py-3 text-sm text-muted-foreground">
                        <span>
                            Dieser Eintrag wurde freigegeben und kann nicht mehr
                            bearbeitet werden.
                        </span>
                    </div>
                )}

                <div className="max-w-2xl space-y-6">
                    <div className="flex flex-wrap items-center gap-2">
                        {labelFor(categories, plantSpecies.category) && (
                            <Badge variant="secondary">
                                {labelFor(categories, plantSpecies.category)}
                            </Badge>
                        )}
                        {plantSpecies.lifecycle && (
                            <Badge variant="outline">
                                {labelFor(lifecycles, plantSpecies.lifecycle)}
                            </Badge>
                        )}
                        <Badge
                            variant="outline"
                            className="text-xs text-muted-foreground"
                        >
                            {plantSpecies.origin === 'system'
                                ? 'System'
                                : 'Community'}
                        </Badge>
                        {isOwner && (
                            <CatalogStatusBadge
                                status={plantSpecies.catalog_status}
                            />
                        )}
                    </div>

                    {plantSpecies.botanical_name && (
                        <p className="text-sm text-muted-foreground italic">
                            {plantSpecies.botanical_name}
                        </p>
                    )}

                    {plantSpecies.alternate_name && (
                        <p className="text-sm text-muted-foreground">
                            Auch bekannt als: {plantSpecies.alternate_name}
                        </p>
                    )}

                    {plantSpecies.description && (
                        <p className="text-sm leading-relaxed text-foreground">
                            {plantSpecies.description}
                        </p>
                    )}

                    {(plantSpecies.sun_exposures?.length ||
                        plantSpecies.soil_types?.length ||
                        plantSpecies.moisture_levels?.length ||
                        plantSpecies.height_min_cm ||
                        plantSpecies.height_max_cm ||
                        plantSpecies.hardiness_note) && (
                        <div className="space-y-2">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Standortansprüche
                            </p>
                            <div className="flex flex-wrap gap-2">
                                {plantSpecies.sun_exposures?.map((v) => (
                                    <Badge
                                        key={v}
                                        variant="outline"
                                        className="gap-1"
                                    >
                                        <Sun className="size-3" />
                                        {labelFor(sunExposures, v)}
                                    </Badge>
                                ))}
                                {plantSpecies.soil_types?.map((v) => (
                                    <Badge
                                        key={v}
                                        variant="outline"
                                        className="gap-1"
                                    >
                                        <Layers className="size-3" />
                                        {labelFor(soilTypes, v)}
                                    </Badge>
                                ))}
                                {plantSpecies.moisture_levels?.map((v) => (
                                    <Badge
                                        key={v}
                                        variant="outline"
                                        className="gap-1"
                                    >
                                        <Droplets className="size-3" />
                                        {labelFor(moistureLevels, v)}
                                    </Badge>
                                ))}
                                {(plantSpecies.height_min_cm ||
                                    plantSpecies.height_max_cm) && (
                                    <Badge variant="outline" className="gap-1">
                                        <Ruler className="size-3" />
                                        {plantSpecies.height_min_cm &&
                                        plantSpecies.height_max_cm
                                            ? `${plantSpecies.height_min_cm}–${plantSpecies.height_max_cm} cm`
                                            : plantSpecies.height_min_cm
                                              ? `ab ${plantSpecies.height_min_cm} cm`
                                              : `bis ${plantSpecies.height_max_cm} cm`}
                                    </Badge>
                                )}
                                {plantSpecies.hardiness_note && (
                                    <Badge variant="outline">
                                        {plantSpecies.hardiness_note}
                                    </Badge>
                                )}
                            </div>
                        </div>
                    )}
                </div>

                <div className="mt-10">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Sorten</h2>
                        {!plantSpecies.archived_at && (
                            <Button asChild size="sm">
                                <Link href={createVariety(plantSpecies).url}>
                                    <Plus className="mr-2 size-4" />
                                    Sorte hinzufügen
                                </Link>
                            </Button>
                        )}
                    </div>

                    {varieties.length === 0 &&
                    archivedVarieties.length === 0 ? (
                        <EmptyState
                            title="Noch keine Sorten"
                            description="Füge eine Sorte hinzu, um diese Pflanzenart weiter zu unterteilen."
                            className="py-10"
                        />
                    ) : (
                        <div className="space-y-6">
                            {varieties.length > 0 && (
                                <VarietyList
                                    varieties={varieties}
                                    plantSpecies={plantSpecies}
                                    userId={userId}
                                    onDeleteVariety={handleDeleteVariety}
                                />
                            )}

                            {archivedVarieties.length > 0 && (
                                <div>
                                    <p className="mb-3 text-sm font-medium text-muted-foreground">
                                        Archiviert
                                    </p>
                                    <VarietyList
                                        varieties={archivedVarieties}
                                        plantSpecies={plantSpecies}
                                        userId={userId}
                                        onDeleteVariety={handleDeleteVariety}
                                    />
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

type VarietyListProps = {
    varieties: PlantVariety[];
    plantSpecies: PlantSpecies;
    userId: number;
    onDeleteVariety: (variety: PlantVariety) => void;
};

function VarietyList({
    varieties,
    plantSpecies,
    userId,
    onDeleteVariety,
}: VarietyListProps) {
    return (
        <ul className="divide-y divide-border rounded-lg border border-border">
            {varieties.map((variety) => {
                const isVarietyOwner =
                    variety.origin === 'user' && variety.created_by === userId;
                const isVarietyEditable =
                    isVarietyOwner &&
                    (variety.catalog_status === 'private' ||
                        variety.catalog_status === 'rejected');

                return (
                    <li
                        key={variety.id}
                        className="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div className="space-y-0.5">
                            <div className="flex items-center gap-2">
                                <p className="text-sm font-medium">
                                    '{variety.name}'
                                    {variety.archived_at && (
                                        <span className="ml-2 text-xs text-muted-foreground">
                                            (archiviert)
                                        </span>
                                    )}
                                </p>
                                {isVarietyOwner &&
                                    variety.catalog_status !== 'approved' && (
                                        <CatalogStatusBadge
                                            status={variety.catalog_status}
                                            className="text-xs"
                                        />
                                    )}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {[
                                    variety.breeder,
                                    variety.year_introduced
                                        ? String(variety.year_introduced)
                                        : null,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                            {isVarietyOwner &&
                                variety.catalog_status === 'rejected' && (
                                    <div className="mt-1 rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-xs">
                                        <span className="font-medium text-destructive">
                                            Abgelehnt
                                        </span>
                                        {variety.moderation_note && (
                                            <p className="mt-0.5 text-foreground">
                                                {variety.moderation_note}
                                            </p>
                                        )}
                                        {variety.duplicate_of && (
                                            <p className="mt-0.5 text-muted-foreground">
                                                Ähnliche Sorte: '
                                                {variety.duplicate_of.name}'
                                            </p>
                                        )}
                                    </div>
                                )}
                        </div>

                        {isVarietyEditable && (
                            <div className="flex shrink-0 items-center gap-2">
                                <Button asChild variant="ghost" size="sm">
                                    <Link
                                        href={
                                            editVariety({
                                                plantSpecies,
                                                variety,
                                            }).url
                                        }
                                    >
                                        <Pencil className="size-4" />
                                        <span className="sr-only">
                                            Bearbeiten
                                        </span>
                                    </Link>
                                </Button>
                                {variety.archived_at ? (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            router.patch(
                                                unarchiveVariety({
                                                    plantSpecies,
                                                    variety,
                                                }).url,
                                            )
                                        }
                                    >
                                        <ArchiveRestore className="size-4" />
                                        <span className="sr-only">
                                            Aktivieren
                                        </span>
                                    </Button>
                                ) : (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            router.patch(
                                                archiveVariety({
                                                    plantSpecies,
                                                    variety,
                                                }).url,
                                            )
                                        }
                                    >
                                        <Archive className="size-4" />
                                        <span className="sr-only">
                                            Archivieren
                                        </span>
                                    </Button>
                                )}
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-destructive hover:text-destructive"
                                    onClick={() => onDeleteVariety(variety)}
                                >
                                    <Trash2 className="size-4" />
                                    <span className="sr-only">Löschen</span>
                                </Button>
                            </div>
                        )}
                    </li>
                );
            })}
        </ul>
    );
}

PlantsShow.layout = (props: { plantSpecies?: PlantSpecies }) => ({
    breadcrumbs: [
        { title: 'Pflanzenkatalog', href: index().url },
        ...(props.plantSpecies
            ? [
                  {
                      title: props.plantSpecies.common_name,
                      href: show(props.plantSpecies).url,
                  },
              ]
            : []),
    ],
});
