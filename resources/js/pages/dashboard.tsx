import { Head, Link } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';

import { index as moderationIndex } from '@/actions/App/Http/Controllers/Plants/PlantModerationController';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboard } from '@/routes';

type ModerationStats = {
    pendingSpeciesCount: number;
    pendingVarietiesCount: number;
};

type PageProps = {
    moderationStats: ModerationStats | null;
};

export default function Dashboard({ moderationStats }: PageProps) {
    const total =
        moderationStats !== null
            ? moderationStats.pendingSpeciesCount +
              moderationStats.pendingVarietiesCount
            : 0;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {moderationStats !== null && (
                    <Link
                        href={moderationIndex().url}
                        className="block rounded-xl border border-border bg-card p-4 transition-colors hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <div className="flex items-center gap-3">
                            <ShieldCheck className="size-5 shrink-0 text-muted-foreground" />
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-medium text-foreground">
                                    {total === 0
                                        ? 'Keine offenen Katalogprüfungen'
                                        : `${total} offene Katalogprüfung${total === 1 ? '' : 'en'}`}
                                </p>
                                {total > 0 && (
                                    <p className="text-xs text-muted-foreground">
                                        {moderationStats.pendingSpeciesCount >
                                            0 && (
                                            <span>
                                                {
                                                    moderationStats.pendingSpeciesCount
                                                }{' '}
                                                {moderationStats.pendingSpeciesCount ===
                                                1
                                                    ? 'Pflanzenart'
                                                    : 'Pflanzenarten'}
                                            </span>
                                        )}
                                        {moderationStats.pendingSpeciesCount >
                                            0 &&
                                            moderationStats.pendingVarietiesCount >
                                                0 && <span> · </span>}
                                        {moderationStats.pendingVarietiesCount >
                                            0 && (
                                            <span>
                                                {
                                                    moderationStats.pendingVarietiesCount
                                                }{' '}
                                                {moderationStats.pendingVarietiesCount ===
                                                1
                                                    ? 'Sorte'
                                                    : 'Sorten'}
                                            </span>
                                        )}
                                    </p>
                                )}
                            </div>
                        </div>
                    </Link>
                )}

                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                    </div>
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
