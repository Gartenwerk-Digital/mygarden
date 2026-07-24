import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import { show as showPlanting } from '@/actions/App/Http/Controllers/GardenPlantings/GardenPlantingController';
import {
    edit,
    update,
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
    PlantingEntry,
    PlantingEntryFormData,
} from '@/types/planting-entry';

type PageProps = {
    garden: Garden;
    planting: GardenPlanting;
    entry: PlantingEntry;
    entryTypes: EntryTypeOption[];
};

export default function PlantingEntriesEdit({
    garden,
    planting,
    entry,
    entryTypes,
}: PageProps) {
    const { data, setData, patch, processing, errors } =
        useForm<PlantingEntryFormData>({
            entry_type: entry.entry_type,
            notes: entry.notes ?? '',
            occurred_at: entry.occurred_at,
            images: [],
            remove_image_ids: [],
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(update({ garden, planting, entry }).url, {
            forceFormData: true,
        });
    };

    return (
        <>
            <Head title="Eintrag bearbeiten" />

            <div className="p-4 md:p-6">
                <PageHeader title="Eintrag bearbeiten" className="mb-6" />

                <div className="max-w-2xl">
                    <PlantingEntryForm
                        data={data}
                        errors={errors}
                        processing={processing}
                        entryTypes={entryTypes}
                        garden={garden}
                        planting={planting}
                        existingEntry={entry}
                        submitLabel="Änderungen speichern"
                        cancelHref={showPlanting({ garden, planting }).url}
                        setData={setData}
                        onSubmit={submit}
                    />
                </div>
            </div>
        </>
    );
}

PlantingEntriesEdit.layout = (props: {
    garden?: Garden;
    planting?: GardenPlanting;
    entry?: PlantingEntry;
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
                          title: 'Eintrag bearbeiten',
                          href: edit({
                              garden: props.garden,
                              planting: props.planting,
                              entry: props.entry ?? { id: 0 },
                          }).url,
                      },
                  ]
                : []),
        ],
    };
};
