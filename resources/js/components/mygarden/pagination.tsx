import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

import { Button } from '@/components/ui/button';
import type { Paginator } from '@/types/ui';

type Props<T> = {
    paginator: Paginator<T>;
};

export function Pagination<T>({ paginator }: Props<T>) {
    if (paginator.last_page <= 1) {
        return null;
    }

    const prevLink = paginator.links.find(
        (l) => l.label.includes('Vorherige') || l.label === '&laquo; Previous',
    );
    const nextLink = paginator.links.find(
        (l) => l.label.includes('Nächste') || l.label === 'Next &raquo;',
    );

    const prevUrl = prevLink?.url ?? paginator.links[0]?.url ?? null;
    const nextUrl =
        nextLink?.url ??
        paginator.links[paginator.links.length - 1]?.url ??
        null;

    return (
        <nav
            className="flex items-center justify-between gap-4"
            aria-label="Pagination"
        >
            <Button
                variant="outline"
                size="sm"
                disabled={prevUrl === null}
                asChild={prevUrl !== null}
            >
                {prevUrl !== null ? (
                    <Link href={prevUrl}>
                        <ChevronLeft className="size-4" />
                        Zurück
                    </Link>
                ) : (
                    <span>
                        <ChevronLeft className="size-4" />
                        Zurück
                    </span>
                )}
            </Button>

            <p className="text-sm text-muted-foreground">
                Seite {paginator.current_page} von {paginator.last_page}
                {paginator.total > 0 && (
                    <span className="ml-1">
                        ({paginator.total}{' '}
                        {paginator.total === 1 ? 'Eintrag' : 'Einträge'})
                    </span>
                )}
            </p>

            <Button
                variant="outline"
                size="sm"
                disabled={nextUrl === null}
                asChild={nextUrl !== null}
            >
                {nextUrl !== null ? (
                    <Link href={nextUrl}>
                        Weiter
                        <ChevronRight className="size-4" />
                    </Link>
                ) : (
                    <span>
                        Weiter
                        <ChevronRight className="size-4" />
                    </span>
                )}
            </Button>
        </nav>
    );
}
