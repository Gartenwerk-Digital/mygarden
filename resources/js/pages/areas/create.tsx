import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import { store } from '@/actions/App/Http/Controllers/Areas/AreaController';
import {
    show as showGarden,
    index as indexGardens,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { AreaForm } from '@/components/areas/area-form';
import type { AreaFormData } from '@/components/areas/area-form';
import { PageHeader } from '@/components/mygarden/page-header';
import type { AreaSummary, Garden } from '@/types';

type PageProps = {
    garden: Garden;
    rootAreas: AreaSummary[];
    parentId?: number | null;
};

export default function AreasCreate({
    garden,
    rootAreas,
    parentId,
}: PageProps) {
    const { data, setData, post, processing, errors } = useForm<AreaFormData>({
        name: '',
        type: 'other',
        description: '',
        size_sqm: '',
        sun_exposure: '',
        soil_type: '',
        moisture_level: '',
        parent_id: parentId ? String(parentId) : '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(store(garden).url);
    };

    return (
        <>
            <Head title="Bereich erstellen" />

            <div className="p-4 md:p-6">
                <PageHeader title="Bereich erstellen" className="mb-6" />

                <div className="max-w-2xl">
                    <AreaForm
                        data={data}
                        errors={errors}
                        processing={processing}
                        rootAreas={rootAreas}
                        setData={setData}
                        onSubmit={submit}
                        submitLabel="Bereich erstellen"
                        cancelHref={showGarden(garden).url}
                    />
                </div>
            </div>
        </>
    );
}

AreasCreate.layout = (props: { garden?: Garden }) => ({
    breadcrumbs: [
        { title: 'Meine Gärten', href: indexGardens().url },
        ...(props.garden
            ? [
                  {
                      title: props.garden.name,
                      href: showGarden(props.garden).url,
                  },
                  { title: 'Bereich erstellen', href: store(props.garden).url },
              ]
            : []),
    ],
});
