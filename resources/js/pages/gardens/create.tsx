import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import {
    index,
    store,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { GardenForm } from '@/components/gardens/garden-form';
import type { GardenFormData } from '@/components/gardens/garden-form';
import { PageHeader } from '@/components/mygarden/page-header';
import type { Countries } from '@/types/garden';

type PageProps = {
    countries: Countries;
};

export default function GardensCreate({ countries }: PageProps) {
    const { data, setData, post, processing, errors } = useForm<GardenFormData>(
        {
            name: '',
            description: '',
            visibility: 'private',
            size_sqm: '',
            country: '',
            region: '',
        },
    );

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="Neuen Garten anlegen" />

            <div className="p-4 md:p-6">
                <PageHeader title="Neuen Garten anlegen" className="mb-6" />

                <div className="max-w-2xl">
                    <GardenForm
                        data={data}
                        errors={errors}
                        processing={processing}
                        countries={countries}
                        setData={setData}
                        onSubmit={submit}
                        submitLabel="Garten anlegen"
                        cancelHref={index().url}
                    />
                </div>
            </div>
        </>
    );
}

GardensCreate.layout = () => ({
    breadcrumbs: [
        { title: 'Meine Gärten', href: index().url },
        { title: 'Neuer Garten', href: store().url },
    ],
});
