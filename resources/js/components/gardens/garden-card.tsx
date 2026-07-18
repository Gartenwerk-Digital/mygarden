import { Link } from '@inertiajs/react';
import { Archive } from 'lucide-react';

import { show } from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { VisibilityBadge } from '@/components/mygarden/visibility-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Garden } from '@/types/garden';

type Props = {
    garden: Garden;
};

export function GardenCard({ garden }: Props) {
    return (
        <Link
            href={show(garden).url}
            className="block rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
        >
            <Card className="h-full transition-colors hover:bg-muted/40">
                <CardHeader className="pb-2">
                    <div className="flex items-start justify-between gap-2">
                        <CardTitle className="text-base leading-snug">
                            {garden.name}
                        </CardTitle>
                        <VisibilityBadge
                            visibility={garden.visibility}
                            className="shrink-0"
                        />
                    </div>
                </CardHeader>
                <CardContent className="space-y-2">
                    {garden.description && (
                        <p className="line-clamp-2 text-sm text-muted-foreground">
                            {garden.description}
                        </p>
                    )}
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                        {garden.size_sqm && (
                            <span>
                                {parseFloat(garden.size_sqm).toLocaleString(
                                    'de-DE',
                                )}{' '}
                                m²
                            </span>
                        )}
                        {garden.region && <span>{garden.region}</span>}
                        {garden.country && !garden.region && (
                            <span>{garden.country}</span>
                        )}
                    </div>
                    {garden.archived_at && (
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
