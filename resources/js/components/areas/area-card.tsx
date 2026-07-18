import { Link } from '@inertiajs/react';
import { Archive } from 'lucide-react';

import { show } from '@/actions/App/Http/Controllers/Areas/AreaController';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { areaTypeIcons, areaTypeLabels } from '@/lib/area-type-icons';
import type { Area, Garden } from '@/types';

type Props = {
    area: Area;
    garden: Garden;
};

export function AreaCard({ area, garden }: Props) {
    const Icon = areaTypeIcons[area.type];
    const childCount = area.children?.length ?? 0;

    return (
        <Link
            href={show({ garden, area }).url}
            className={`block rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none ${area.archived_at ? 'opacity-60' : ''}`}
        >
            <Card className="h-full transition-colors hover:bg-muted/40">
                <CardHeader className="pb-2">
                    <div className="flex items-start justify-between gap-2">
                        <div className="flex min-w-0 items-center gap-2">
                            <Icon className="size-4 shrink-0 text-muted-foreground" />
                            <CardTitle className="truncate text-base leading-snug">
                                {area.name}
                            </CardTitle>
                        </div>
                        <Badge variant="secondary" className="shrink-0 text-xs">
                            {areaTypeLabels[area.type]}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent className="space-y-2">
                    {area.description && (
                        <p className="line-clamp-2 text-sm text-muted-foreground">
                            {area.description}
                        </p>
                    )}
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                        {area.size_sqm && (
                            <span>
                                {parseFloat(area.size_sqm).toLocaleString(
                                    'de-DE',
                                )}{' '}
                                m²
                            </span>
                        )}
                        {childCount > 0 && (
                            <span>
                                {childCount}{' '}
                                {childCount === 1
                                    ? 'Unterbereich'
                                    : 'Unterbereiche'}
                            </span>
                        )}
                    </div>
                    {area.archived_at && (
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
