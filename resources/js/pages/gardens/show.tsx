import { Head, Link, router } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    MapPin,
    Pencil,
    Ruler,
    Trash2,
} from 'lucide-react';

import {
    archive,
    destroy,
    edit,
    index,
    show,
    unarchive,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { PageHeader } from '@/components/mygarden/page-header';
import { VisibilityBadge } from '@/components/mygarden/visibility-badge';
import { Button } from '@/components/ui/button';
import type { Garden } from '@/types/garden';

type PageProps = {
    garden: Garden;
};

export default function GardensShow({ garden }: PageProps) {
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
