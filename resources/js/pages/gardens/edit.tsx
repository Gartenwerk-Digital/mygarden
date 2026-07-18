import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import {
    index,
    show,
    update,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { GardenForm } from '@/components/gardens/garden-form';
import type { GardenFormData } from '@/components/gardens/garden-form';
import { PageHeader } from '@/components/mygarden/page-header';
import type { Countries, Garden } from '@/types/garden';

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

                <div className="max-w-2xl">
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
