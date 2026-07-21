import { Head, Link, router, usePage } from '@inertiajs/react';
import { Leaf, Plus, Search } from 'lucide-react';
import { useRef } from 'react';
import type { FormEventHandler } from 'react';

import {
    create,
    index,
} from '@/actions/App/Http/Controllers/Plants/PlantSpeciesController';
import { EmptyState } from '@/components/mygarden/empty-state';
import { PageHeader } from '@/components/mygarden/page-header';
import { Pagination } from '@/components/mygarden/pagination';
import { PlantSpeciesCard } from '@/components/plants/plant-species-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Auth } from '@/types/auth';
import type { EnumOption, PlantSpecies } from '@/types/plant';
import type { Paginator } from '@/types/ui';

type Scope = 'catalog' | 'mine' | 'archived';

type Filters = {
    search: string | null;
    category: string | null;
    scope: Scope;
    catalog_status: string | null;
};

type PageProps = {
    species: Paginator<PlantSpecies>;
    filters: Filters;
    categories: EnumOption[];
    lifecycles: EnumOption[];
};

export default function PlantsIndex({
    species,
    filters,
    categories,
    lifecycles,
}: PageProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const currentUserId = auth.user.id;
    const formRef = useRef<HTMLFormElement>(null);

    const navigate = (params: Record<string, string>) => {
        router.get(index().url, params, { preserveState: true });
    };

    const buildParams = (overrides: Record<string, string | null> = {}) => {
        const base: Record<string, string> = {};

        if (filters.search) {
            base.search = filters.search;
        }

        if (filters.category) {
            base.category = filters.category;
        }

        if (filters.scope !== 'catalog') {
            base.scope = filters.scope;
        }

        const merged = { ...base, ...overrides };

        return Object.fromEntries(
            Object.entries(merged).filter(([, v]) => v !== null && v !== ''),
        ) as Record<string, string>;
    };

    const handleCategoryChange = (value: string) => {
        navigate(buildParams({ category: value === 'all' ? null : value }));
    };

    const handleScopeChange = (value: string) => {
        navigate(buildParams({ scope: value === 'catalog' ? null : value }));
    };

    const handleSearch: FormEventHandler = (e) => {
        e.preventDefault();
        const form = formRef.current;

        if (!form) {
            return;
        }

        const search = (
            form.elements.namedItem('search') as HTMLInputElement
        ).value.trim();
        navigate(buildParams({ search: search || null }));
    };

    const hasSpecies = species.data.length > 0;

    const emptyTitle = () => {
        if (filters.search || filters.category) {
            return 'Keine Pflanzen gefunden';
        }

        if (filters.scope === 'archived') {
            return 'Keine archivierten Pflanzen';
        }

        if (filters.scope === 'mine') {
            return 'Noch keine eigenen Einträge';
        }

        return 'Katalog ist noch leer';
    };

    return (
        <>
            <Head title="Pflanzenkatalog" />

            <div className="p-4 md:p-6">
                <PageHeader
                    title="Pflanzenkatalog"
                    description="Durchsuche und verwalte Pflanzen für deine Gärten."
                    actions={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="mr-2 size-4" />
                                Pflanze anlegen
                            </Link>
                        </Button>
                    }
                />

                <div className="mb-6 flex flex-col gap-3 sm:flex-row">
                    <form
                        ref={formRef}
                        onSubmit={handleSearch}
                        className="flex flex-1 gap-2"
                    >
                        <div className="relative flex-1">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Name oder botanischer Name …"
                                className="pl-9"
                            />
                        </div>
                        <Button type="submit" variant="secondary">
                            Suchen
                        </Button>
                    </form>

                    <Select
                        value={filters.category ?? 'all'}
                        onValueChange={handleCategoryChange}
                    >
                        <SelectTrigger className="w-full sm:w-52">
                            <SelectValue placeholder="Alle Kategorien" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Alle Kategorien</SelectItem>
                            {categories.map(({ value, label }) => (
                                <SelectItem key={value} value={value}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.scope}
                        onValueChange={handleScopeChange}
                    >
                        <SelectTrigger className="w-full sm:w-52">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="catalog">Katalog</SelectItem>
                            <SelectItem value="mine">Meine Einträge</SelectItem>
                            <SelectItem value="archived">Archiviert</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {!hasSpecies ? (
                    <EmptyState
                        icon={Leaf}
                        title={emptyTitle()}
                        description={
                            filters.search || filters.category
                                ? 'Ändere deine Suche oder den Filter.'
                                : 'Leg die erste Pflanzenart an.'
                        }
                        action={
                            !filters.search && !filters.category ? (
                                <Button asChild>
                                    <Link href={create().url}>
                                        <Plus className="mr-2 size-4" />
                                        Erste Pflanze anlegen
                                    </Link>
                                </Button>
                            ) : undefined
                        }
                    />
                ) : (
                    <div className="space-y-8">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {species.data.map((s) => (
                                <PlantSpeciesCard
                                    key={s.id}
                                    plantSpecies={s}
                                    categories={categories}
                                    lifecycles={lifecycles}
                                    currentUserId={currentUserId}
                                />
                            ))}
                        </div>

                        <Pagination paginator={species} />
                    </div>
                )}
            </div>
        </>
    );
}

PlantsIndex.layout = () => ({
    breadcrumbs: [{ title: 'Pflanzenkatalog', href: index().url }],
});
