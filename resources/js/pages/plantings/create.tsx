import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import {
    store,
    create,
} from '@/actions/App/Http/Controllers/GardenPlantings/GardenPlantingController';
import {
    index as indexGardens,
    show as showGarden,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { PageHeader } from '@/components/mygarden/page-header';
import { PlantingForm } from '@/components/plantings/planting-form';
import type { Garden } from '@/types/garden';
import type { PlantingFormData, PlantSpeciesOption } from '@/types/planting';

type AreaOption = {
    id: number;
    name: string;
    full_label: string;
};

type EnumOption = {
    value: string;
    label: string;
};

type PageProps = {
    garden: Garden;
    areas: AreaOption[];
    availableSpecies: PlantSpeciesOption[];
    statuses: EnumOption[];
    units: EnumOption[];
    preselectedAreaId: number | null;
};

export default function PlantingsCreate({
    garden,
    areas,
    availableSpecies,
    statuses,
    units,
    preselectedAreaId,
}: PageProps) {
    const { data, setData, post, processing, errors } =
        useForm<PlantingFormData>({
            plant_species_id: '',
            plant_variety_id: '',
            area_id: preselectedAreaId ?? '',
            status: 'planned',
            quantity: '',
            quantity_unit: '',
            nickname: '',
            notes: '',
            planted_at: '',
            acquired_at: '',
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(store(garden).url);
    };

    return (
        <>
            <Head title="Pflanzung anlegen" />

            <div className="p-4 md:p-6">
                <PageHeader title="Pflanzung anlegen" className="mb-6" />

                <div className="max-w-2xl">
                    <PlantingForm
                        data={data}
                        errors={errors}
                        processing={processing}
                        availableSpecies={availableSpecies}
                        areas={areas}
                        statuses={statuses}
                        units={units}
                        submitLabel="Pflanzung anlegen"
                        cancelHref={showGarden(garden).url}
                        setData={setData}
                        onSubmit={submit}
                    />
                </div>
            </div>
        </>
    );
}

PlantingsCreate.layout = (props: { garden?: Garden }) => ({
    breadcrumbs: [
        { title: 'Meine Gärten', href: indexGardens().url },
        ...(props.garden
            ? [
                  {
                      title: props.garden.name,
                      href: showGarden(props.garden).url,
                  },
                  {
                      title: 'Pflanzung anlegen',
                      href: create(props.garden).url,
                  },
              ]
            : []),
    ],
});
