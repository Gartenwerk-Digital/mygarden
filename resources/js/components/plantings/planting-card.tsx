import { Link } from '@inertiajs/react';
import { Archive, MapPin } from 'lucide-react';

import { show } from '@/actions/App/Http/Controllers/GardenPlantings/GardenPlantingController';
import { PlantingStatusBadge } from '@/components/plantings/planting-status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Garden } from '@/types/garden';
import type { GardenPlantingSummary } from '@/types/planting';
import { formatQuantity } from '@/types/planting';

type Props = {
    planting: GardenPlantingSummary;
    garden: Garden;
};

export function PlantingCard({ planting, garden }: Props) {
    const displayName = planting.nickname ?? planting.species.common_name;
    const isArchived = planting.species.archived_at !== null;
    const quantityLabel = formatQuantity(
        planting.quantity,
        planting.quantity_unit,
    );

    return (
        <Link
            href={show({ garden, planting }).url}
            className="block rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
        >
            <Card className="h-full transition-colors hover:bg-muted/40">
                <CardHeader className="pb-2">
                    <div className="flex items-start justify-between gap-2">
                        <CardTitle className="truncate text-base leading-snug">
                            {displayName}
                        </CardTitle>
                        <PlantingStatusBadge
                            status={planting.status}
                            className="shrink-0"
                        />
                    </div>
                </CardHeader>
                <CardContent className="space-y-2">
                    {planting.nickname && (
                        <p className="text-sm text-muted-foreground">
                            {planting.species.common_name}
                            {planting.variety && (
                                <> · {planting.variety.name}</>
                            )}
                        </p>
                    )}
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                        {quantityLabel && <span>{quantityLabel}</span>}
                        {planting.area && (
                            <span className="flex items-center gap-1">
                                <MapPin className="size-3" />
                                {planting.area.deleted_at
                                    ? 'Bereich gelöscht'
                                    : planting.area.archived_at
                                      ? `${planting.area.name} (archiviert)`
                                      : planting.area.name}
                            </span>
                        )}
                    </div>
                    {isArchived && (
                        <div className="flex items-center gap-1 text-xs text-muted-foreground">
                            <Archive className="size-3" />
                            <span>Pflanze archiviert</span>
                        </div>
                    )}
                </CardContent>
            </Card>
        </Link>
    );
}
