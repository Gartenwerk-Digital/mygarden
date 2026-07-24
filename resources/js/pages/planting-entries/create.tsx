import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import { show as showPlanting } from '@/actions/App/Http/Controllers/GardenPlantings/GardenPlantingController';
import {
    create,
    store,
} from '@/actions/App/Http/Controllers/GardenPlantings/PlantingEntryController';
import {
    index as indexGardens,
    show as showGarden,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { PageHeader } from '@/components/mygarden/page-header';
import { PlantingEntryForm } from '@/components/plantings/planting-entry-form';
import type { Garden } from '@/types/garden';
import type { GardenPlanting } from '@/types/planting';
import type {
    EntryTypeOption,
    PlantingEntryFormData,
} from '@/types/planting-entry';

type PageProps = {
    garden: Garden;
    planting: GardenPlanting;
    entryTypes: EntryTypeOption[];
};

export default function PlantingEntriesCreate({
    garden,
    planting,
    entryTypes,
}: PageProps) {
    const today = new Date().toISOString().split('T')[0] ?? '';

    const { data, setData, post, processing, errors } =
        useForm<PlantingEntryFormData>({
            entry_type: 'observation',
            notes: '',
            occurred_at: today,
            images: [],
            remove_image_ids: [],
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(store({ garden, planting }).url, {
            forceFormData: true,
        });
    };

    return (
        <>
            <Head title="Eintrag anlegen" />

            <div className="p-4 md:p-6">
                <PageHeader title="Eintrag anlegen" className="mb-6" />

                <div className="max-w-2xl">
                    <PlantingEntryForm
                        data={data}
                        errors={errors}
                        processing={processing}
                        entryTypes={entryTypes}
                        garden={garden}
                        planting={planting}
                        submitLabel="Eintrag anlegen"
                        cancelHref={showPlanting({ garden, planting }).url}
                        setData={setData}
                        onSubmit={submit}
                    />
                </div>
            </div>
        </>
    );
}

PlantingEntriesCreate.layout = (props: {
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
                          href: showPlanting({
                              garden: props.garden,
                              planting: props.planting,
                          }).url,
                      },
                      {
                          title: 'Eintrag anlegen',
                          href: create({
                              garden: props.garden,
                              planting: props.planting,
                          }).url,
                      },
                  ]
                : []),
        ],
    };
};
