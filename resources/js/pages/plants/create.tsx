import { Head, Link, useForm } from '@inertiajs/react';
import { Info } from 'lucide-react';
import type { FormEventHandler } from 'react';

import {
    index,
    store,
} from '@/actions/App/Http/Controllers/Plants/PlantSpeciesController';
import { PageHeader } from '@/components/mygarden/page-header';
import { PlantSpeciesForm } from '@/components/plants/plant-species-form';
import type { PlantSpeciesFormData } from '@/components/plants/plant-species-form';
import type { EnumOption } from '@/types/plant';

type PageProps = {
    categories: EnumOption[];
    lifecycles: EnumOption[];
    sunExposures: EnumOption[];
    soilTypes: EnumOption[];
    moistureLevels: EnumOption[];
};

export default function PlantsCreate({
    categories,
    lifecycles,
    sunExposures,
    soilTypes,
    moistureLevels,
}: PageProps) {
    const { data, setData, post, processing, errors } =
        useForm<PlantSpeciesFormData>({
            common_name: '',
            botanical_name: '',
            alternate_name: '',
            category: '',
            description: '',
            lifecycle: '',
            hardiness_note: '',
            sun_exposures: [],
            soil_types: [],
            moisture_levels: [],
            height_min_cm: '',
            height_max_cm: '',
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="Pflanze anlegen" />

            <div className="p-4 md:p-6">
                <PageHeader title="Pflanze anlegen" className="mb-6" />

                <div className="mb-4 flex items-start gap-2 rounded-lg border border-border bg-muted/50 px-4 py-3 text-sm text-muted-foreground">
                    <Info className="mt-0.5 size-4 shrink-0" />
                    <span>
                        Dein Eintrag wird zunächst privat angelegt.{' '}
                        <Link
                            href={index().url}
                            className="underline hover:text-foreground"
                        >
                            Suche zuerst im Katalog
                        </Link>
                        , ob diese Pflanze bereits vorhanden ist.
                    </span>
                </div>

                <div className="max-w-2xl">
                    <PlantSpeciesForm
                        data={data}
                        errors={errors}
                        processing={processing}
                        categories={categories}
                        lifecycles={lifecycles}
                        sunExposures={sunExposures}
                        soilTypes={soilTypes}
                        moistureLevels={moistureLevels}
                        setData={setData}
                        onSubmit={submit}
                        submitLabel="Pflanze anlegen"
                        cancelHref={index().url}
                    />
                </div>
            </div>
        </>
    );
}

PlantsCreate.layout = () => ({
    breadcrumbs: [
        { title: 'Pflanzenkatalog', href: index().url },
        { title: 'Pflanze anlegen', href: '' },
    ],
});
