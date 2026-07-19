import { Link } from '@inertiajs/react';
import { Archive } from 'lucide-react';

import { show } from '@/actions/App/Http/Controllers/Plants/PlantSpeciesController';
import { CatalogStatusBadge } from '@/components/plants/catalog-status-badge';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { plantCategoryIcons } from '@/lib/plant-icons';
import type { EnumOption, PlantSpecies } from '@/types/plant';

type Props = {
    plantSpecies: PlantSpecies;
    categories: EnumOption[];
    lifecycles: EnumOption[];
    currentUserId?: number;
};

export function PlantSpeciesCard({
    plantSpecies,
    categories,
    lifecycles,
    currentUserId,
}: Props) {
    const Icon = plantCategoryIcons[plantSpecies.category];
    const categoryLabel =
        categories.find((c) => c.value === plantSpecies.category)?.label ??
        plantSpecies.category;
    const lifecycleLabel = plantSpecies.lifecycle
        ? lifecycles.find((l) => l.value === plantSpecies.lifecycle)?.label
        : null;
    const isOwnEntry =
        plantSpecies.origin === 'user' &&
        plantSpecies.created_by === currentUserId;
    const showStatusBadge =
        isOwnEntry && plantSpecies.catalog_status !== 'approved';

    return (
        <Link
            href={show(plantSpecies).url}
            className="block rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
        >
            <Card className="h-full transition-colors hover:bg-muted/40">
                <CardHeader className="pb-2">
                    <div className="flex items-start justify-between gap-2">
                        <div className="flex min-w-0 items-center gap-2">
                            <Icon className="size-4 shrink-0 text-muted-foreground" />
                            <CardTitle className="truncate text-base leading-snug">
                                {plantSpecies.common_name}
                            </CardTitle>
                        </div>
                        <Badge variant="secondary" className="shrink-0 text-xs">
                            {categoryLabel}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent className="space-y-2">
                    {plantSpecies.botanical_name && (
                        <p className="text-sm text-muted-foreground italic">
                            {plantSpecies.botanical_name}
                        </p>
                    )}
                    {plantSpecies.description &&
                        !plantSpecies.botanical_name && (
                            <p className="line-clamp-2 text-sm text-muted-foreground">
                                {plantSpecies.description}
                            </p>
                        )}
                    <div className="flex flex-wrap items-center gap-1">
                        {lifecycleLabel && (
                            <Badge variant="outline" className="text-xs">
                                {lifecycleLabel}
                            </Badge>
                        )}
                        {showStatusBadge && (
                            <CatalogStatusBadge
                                status={plantSpecies.catalog_status}
                                className="text-xs"
                            />
                        )}
                    </div>
                    {plantSpecies.archived_at && (
                        <div className="flex items-center gap-1 text-xs text-muted-foreground">
                            <Archive className="size-3" />
                            <span>Archiviert</span>
                        </div>
                    )}
                </CardContent>
            </Card>
        </Link>
    );
}
