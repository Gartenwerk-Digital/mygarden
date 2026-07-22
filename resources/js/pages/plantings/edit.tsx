import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import {
    edit,
    show,
    update,
} from '@/actions/App/Http/Controllers/GardenPlantings/GardenPlantingController';
import {
    index as indexGardens,
    show as showGarden,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { PageHeader } from '@/components/mygarden/page-header';
import { PlantingForm } from '@/components/plantings/planting-form';
import type { Garden } from '@/types/garden';
import type {
    GardenPlanting,
    PlantingFormData,
    PlantSpeciesOption,
} from '@/types/planting';

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
    planting: GardenPlanting;
    areas: AreaOption[];
    availableSpecies: PlantSpeciesOption[];
    statuses: EnumOption[];
    units: EnumOption[];
};

export default function PlantingsEdit({
    garden,
    planting,
    areas,
    availableSpecies,
    statuses,
    units,
}: PageProps) {
    const { data, setData, patch, processing, errors } =
        useForm<PlantingFormData>({
            plant_species_id: planting.plant_species_id,
            plant_variety_id: planting.plant_variety_id ?? '',
            area_id: planting.area_id ?? '',
            status: planting.status,
            quantity: planting.quantity ?? '',
            quantity_unit: planting.quantity_unit ?? '',
            nickname: planting.nickname ?? '',
            notes: planting.notes ?? '',
            planted_at: planting.planted_at ?? '',
            acquired_at: planting.acquired_at ?? '',
        });

    const displayName =
        planting.nickname ?? planting.species?.common_name ?? 'Pflanzung';

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(update({ garden, planting }).url);
    };

    return (
        <>
            <Head title={`${displayName} bearbeiten`} />

            <div className="p-4 md:p-6">
                <PageHeader
                    title={`${displayName} bearbeiten`}
                    className="mb-6"
                />

                <div className="max-w-2xl">
                    <PlantingForm
                        data={data}
                        errors={errors}
                        processing={processing}
                        availableSpecies={availableSpecies}
                        areas={areas}
                        statuses={statuses}
                        units={units}
                        submitLabel="Änderungen speichern"
                        cancelHref={show({ garden, planting }).url}
                        setData={setData}
                        onSubmit={submit}
                    />
                </div>
            </div>
        </>
    );
}

PlantingsEdit.layout = (props: {
    garden?: Garden;
    planting?: GardenPlanting;
}) => {
    const displayName =
        props.planting?.nickname ??
        props.planting?.species?.common_name ??
        'Pflanzung';

    return {
        breadcrumbs: [
            { title: 'Meine Gärten', href: indexGardens().url },
            ...(props.garden
                ? [
                      {
                          title: props.garden.name,
                          href: showGarden(props.garden).url,
                      },
                  ]
                : []),
            ...(props.garden && props.planting
                ? [
                      {
                          title: displayName,
                          href: show({
                              garden: props.garden,
                              planting: props.planting,
                          }).url,
                      },
                      {
                          title: 'Bearbeiten',
                          href: edit({
                              garden: props.garden,
                              planting: props.planting,
                          }).url,
                      },
                  ]
                : []),
        ],
    };
};
