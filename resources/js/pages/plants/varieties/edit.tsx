import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import {
    index,
    show,
} from '@/actions/App/Http/Controllers/Plants/PlantSpeciesController';
import { update } from '@/actions/App/Http/Controllers/Plants/PlantVarietyController';
import { PageHeader } from '@/components/mygarden/page-header';
import { PlantVarietyForm } from '@/components/plants/plant-variety-form';
import type { PlantVarietyFormData } from '@/components/plants/plant-variety-form';
import type { PlantSpecies, PlantVariety } from '@/types/plant';

type PageProps = {
    plantSpecies: PlantSpecies;
    variety: PlantVariety;
};

export default function PlantVarietiesEdit({
    plantSpecies,
    variety,
}: PageProps) {
    const { data, setData, patch, processing, errors } =
        useForm<PlantVarietyFormData>({
            name: variety.name,
            breeder: variety.breeder ?? '',
            year_introduced: variety.year_introduced?.toString() ?? '',
            description: variety.description ?? '',
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(update({ plantSpecies, variety }).url);
    };

    return (
        <>
            <Head title={`${variety.name} bearbeiten`} />

            <div className="p-4 md:p-6">
                <PageHeader
                    title={`'${variety.name}' bearbeiten`}
                    description={plantSpecies.common_name}
                    className="mb-6"
                />

                <div className="max-w-xl">
                    <PlantVarietyForm
                        data={data}
                        errors={errors}
                        processing={processing}
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

PlantVarietiesEdit.layout = (props: {
    plantSpecies?: PlantSpecies;
    variety?: PlantVariety;
}) => ({
    breadcrumbs: [
        { title: 'Pflanzenkatalog', href: index().url },
        ...(props.plantSpecies
            ? [
                  {
                      title: props.plantSpecies.common_name,
                      href: show(props.plantSpecies).url,
                  },
                  {
                      title: `'${props.variety?.name ?? ''}' bearbeiten`,
                      href: '',
                  },
              ]
            : []),
    ],
});
