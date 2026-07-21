import { Head, Link } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { useState } from 'react';

import {
    index,
    showSpecies,
    showVariety,
} from '@/actions/App/Http/Controllers/Plants/PlantModerationController';
import { EmptyState } from '@/components/mygarden/empty-state';
import { PageHeader } from '@/components/mygarden/page-header';
import { Pagination } from '@/components/mygarden/pagination';
import { Button } from '@/components/ui/button';
import type {
    ModerationSpeciesSummary,
    ModerationVarietySummary,
} from '@/types/plant';
import type { Paginator } from '@/types/ui';

type PageProps = {
    species: Paginator<ModerationSpeciesSummary>;
    varieties: Paginator<ModerationVarietySummary>;
};

type Tab = 'species' | 'varieties';

function formatDate(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

export default function ModerationPlantsIndex({
    species,
    varieties,
}: PageProps) {
    const [activeTab, setActiveTab] = useState<Tab>('species');

    return (
        <>
            <Head title="Moderierung – Pflanzenkatalog" />

            <div className="p-4 md:p-6">
                <PageHeader
                    title="Moderierung"
                    description="Eingereichte Pflanzenarten und Sorten prüfen."
                />

                <div className="mb-6 flex gap-2 border-b border-border">
                    <button
                        type="button"
                        onClick={() => setActiveTab('species')}
                        className={`border-b-2 px-4 pb-3 text-sm font-medium transition-colors ${
                            activeTab === 'species'
                                ? 'border-foreground text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        Arten
                        {species.total > 0 && (
                            <span className="ml-2 rounded-full bg-muted px-2 py-0.5 font-mono text-xs text-muted-foreground">
                                {species.total}
                            </span>
                        )}
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('varieties')}
                        className={`border-b-2 px-4 pb-3 text-sm font-medium transition-colors ${
                            activeTab === 'varieties'
                                ? 'border-foreground text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        Sorten
                        {varieties.total > 0 && (
                            <span className="ml-2 rounded-full bg-muted px-2 py-0.5 font-mono text-xs text-muted-foreground">
                                {varieties.total}
                            </span>
                        )}
                    </button>
                </div>

                {activeTab === 'species' && (
                    <div className="space-y-6">
                        {species.data.length === 0 ? (
                            <EmptyState
                                icon={ShieldCheck}
                                title="Keine Einreichungen"
                                description="Alle Pflanzenarten wurden geprüft."
                                className="py-16"
                            />
                        ) : (
                            <>
                                <ul className="divide-y divide-border rounded-lg border border-border">
                                    {species.data.map((s) => (
                                        <li
                                            key={s.id}
                                            className="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div>
                                                <p className="text-sm font-medium text-foreground">
                                                    {s.common_name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {s.creator
                                                        ? s.creator.username
                                                        : 'Gelöschter Nutzer'}{' '}
                                                    · Eingereicht{' '}
                                                    {formatDate(s.submitted_at)}
                                                </p>
                                            </div>
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link href={showSpecies(s).url}>
                                                    Prüfen
                                                </Link>
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                                <Pagination paginator={species} />
                            </>
                        )}
                    </div>
                )}

                {activeTab === 'varieties' && (
                    <div className="space-y-6">
                        {varieties.data.length === 0 ? (
                            <EmptyState
                                icon={ShieldCheck}
                                title="Keine Einreichungen"
                                description="Alle Sorten wurden geprüft."
                                className="py-16"
                            />
                        ) : (
                            <>
                                <ul className="divide-y divide-border rounded-lg border border-border">
                                    {varieties.data.map((v) => (
                                        <li
                                            key={v.id}
                                            className="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div>
                                                <p className="text-sm font-medium text-foreground">
                                                    '{v.name}'
                                                    {v.species && (
                                                        <span className="ml-1 font-normal text-muted-foreground">
                                                            ·{' '}
                                                            {
                                                                v.species
                                                                    .common_name
                                                            }
                                                        </span>
                                                    )}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {v.creator
                                                        ? v.creator.username
                                                        : 'Gelöschter Nutzer'}{' '}
                                                    · Eingereicht{' '}
                                                    {formatDate(v.submitted_at)}
                                                </p>
                                            </div>
                                            {v.species && (
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <Link
                                                        href={
                                                            showVariety({
                                                                plantSpecies:
                                                                    v.species,
                                                                variety: v,
                                                            }).url
                                                        }
                                                    >
                                                        Prüfen
                                                    </Link>
                                                </Button>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                                <Pagination paginator={varieties} />
                            </>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

ModerationPlantsIndex.layout = () => ({
    breadcrumbs: [{ title: 'Moderierung', href: index().url }],
});
