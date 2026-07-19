import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import {
    index,
    show,
    update,
} from '@/actions/App/Http/Controllers/Plants/PlantSpeciesController';
import { PageHeader } from '@/components/mygarden/page-header';
import { PlantSpeciesForm } from '@/components/plants/plant-species-form';
import type { PlantSpeciesFormData } from '@/components/plants/plant-species-form';
import type { EnumOption, PlantSpecies } from '@/types/plant';

type PageProps = {
    plantSpecies: PlantSpecies;
    categories: EnumOption[];
    lifecycles: EnumOption[];
    sunExposures: EnumOption[];
    soilTypes: EnumOption[];
    moistureLevels: EnumOption[];
};

export default function PlantsEdit({
    plantSpecies,
    categories,
    lifecycles,
    sunExposures,
    soilTypes,
    moistureLevels,
}: PageProps) {
    const { data, setData, patch, processing, errors } =
        useForm<PlantSpeciesFormData>({
            common_name: plantSpecies.common_name,
            botanical_name: plantSpecies.botanical_name ?? '',
            alternate_name: plantSpecies.alternate_name ?? '',
            category: plantSpecies.category,
            description: plantSpecies.description ?? '',
            lifecycle: plantSpecies.lifecycle ?? '',
            hardiness_note: plantSpecies.hardiness_note ?? '',
            sun_exposures: plantSpecies.sun_exposures ?? [],
            soil_types: plantSpecies.soil_types ?? [],
            moisture_levels: plantSpecies.moisture_levels ?? [],
            height_min_cm: plantSpecies.height_min_cm?.toString() ?? '',
            height_max_cm: plantSpecies.height_max_cm?.toString() ?? '',
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(update(plantSpecies).url);
    };

    return (
        <>
            <Head title={`${plantSpecies.common_name} bearbeiten`} />

            <div className="p-4 md:p-6">
                <PageHeader
                    title={`${plantSpecies.common_name} bearbeiten`}
                    className="mb-6"
                />

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
                        submitLabel="Änderungen speichern"
                        cancelHref={show(plantSpecies).url}
                    />
                </div>
            </div>
        </>
    );
}

PlantsEdit.layout = (props: { plantSpecies?: PlantSpecies }) => ({
    breadcrumbs: [
        { title: 'Pflanzenkatalog', href: index().url },
        ...(props.plantSpecies
            ? [
                  {
                      title: props.plantSpecies.common_name,
                      href: show(props.plantSpecies).url,
                  },
                  { title: 'Bearbeiten', href: '' },
              ]
            : []),
    ],
});
