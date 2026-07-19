import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import {
    index,
    show,
} from '@/actions/App/Http/Controllers/Plants/PlantSpeciesController';
import { store } from '@/actions/App/Http/Controllers/Plants/PlantVarietyController';
import { PageHeader } from '@/components/mygarden/page-header';
import { PlantVarietyForm } from '@/components/plants/plant-variety-form';
import type { PlantVarietyFormData } from '@/components/plants/plant-variety-form';
import type { PlantSpecies } from '@/types/plant';

type PageProps = {
    plantSpecies: PlantSpecies;
};

export default function PlantVarietiesCreate({ plantSpecies }: PageProps) {
    const { data, setData, post, processing, errors } =
        useForm<PlantVarietyFormData>({
            name: '',
            breeder: '',
            year_introduced: '',
            description: '',
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(store(plantSpecies).url);
    };

    return (
        <>
            <Head title="Sorte anlegen" />

            <div className="p-4 md:p-6">
                <PageHeader
                    title="Sorte anlegen"
                    description={`Neue Sorte für „${plantSpecies.common_name}"`}
                    className="mb-6"
                />

                <div className="max-w-xl">
                    <PlantVarietyForm
                        data={data}
                        errors={errors}
                        processing={processing}
                        setData={setData}
                        onSubmit={submit}
                        submitLabel="Sorte anlegen"
                        cancelHref={show(plantSpecies).url}
                    />
                </div>
            </div>
        </>
    );
}

PlantVarietiesCreate.layout = (props: { plantSpecies?: PlantSpecies }) => ({
    breadcrumbs: [
        { title: 'Pflanzenkatalog', href: index().url },
        ...(props.plantSpecies
            ? [
                  {
                      title: props.plantSpecies.common_name,
                      href: show(props.plantSpecies).url,
                  },
                  { title: 'Sorte anlegen', href: '' },
              ]
            : []),
    ],
});
