import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import {
    index,
    show,
    update,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { GardenForm } from '@/components/gardens/garden-form';
import type { GardenFormData } from '@/components/gardens/garden-form';
import { GardenImageGallery } from '@/components/gardens/garden-image-gallery';
import { GardenImageUpload } from '@/components/gardens/garden-image-upload';
import { FormSection } from '@/components/mygarden/form-section';
import { PageHeader } from '@/components/mygarden/page-header';
import type { Countries, Garden } from '@/types/garden';

const MAX_IMAGES_PER_GARDEN = 20;

type PageProps = {
    garden: Garden;
    countries: Countries;
};

export default function GardensEdit({ garden, countries }: PageProps) {
    const { data, setData, patch, processing, errors } =
        useForm<GardenFormData>({
            name: garden.name,
            description: garden.description ?? '',
            visibility: garden.visibility,
            size_sqm: garden.size_sqm ?? '',
            country: garden.country ?? '',
            region: garden.region ?? '',
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(update(garden).url);
    };

    return (
        <>
            <Head title={`${garden.name} bearbeiten`} />

            <div className="p-4 md:p-6">
                <PageHeader title="Garten bearbeiten" className="mb-6" />

                <div className="max-w-2xl space-y-10">
                    <GardenForm
                        data={data}
                        errors={errors}
                        processing={processing}
                        countries={countries}
                        setData={setData}
                        onSubmit={submit}
                        submitLabel="Änderungen speichern"
                        cancelHref={show(garden).url}
                    />

                    <FormSection
                        title="Bilder verwalten"
                        description={`Bis zu ${MAX_IMAGES_PER_GARDEN} Bilder pro Garten. Ein Bild kann als Titelbild markiert werden.`}
                    >
                        <div className="space-y-6">
                            <GardenImageGallery
                                garden={garden}
                                images={garden.images ?? []}
                            />
                            <GardenImageUpload
                                garden={garden}
                                remainingSlots={
                                    MAX_IMAGES_PER_GARDEN -
                                    (garden.images?.length ?? 0)
                                }
                            />
                        </div>
                    </FormSection>
                </div>
            </div>
        </>
    );
}

GardensEdit.layout = (props: { garden?: Garden }) => ({
    breadcrumbs: [
        { title: 'Meine Gärten', href: index().url },
        ...(props.garden
            ? [
                  { title: props.garden.name, href: show(props.garden).url },
                  {
                      title: 'Bearbeiten',
                      href: `${show(props.garden).url}/edit`,
                  },
              ]
            : []),
    ],
});
