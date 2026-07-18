import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import {
    show,
    update,
} from '@/actions/App/Http/Controllers/Areas/AreaController';
import {
    index as indexGardens,
    show as showGarden,
} from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { AreaForm } from '@/components/areas/area-form';
import type { AreaFormData } from '@/components/areas/area-form';
import { PageHeader } from '@/components/mygarden/page-header';
import type { Area, AreaSummary, Garden } from '@/types';

type PageProps = {
    garden: Garden;
    area: Area;
    rootAreas: AreaSummary[];
    hasChildren: boolean;
};

export default function AreasEdit({
    garden,
    area,
    rootAreas,
    hasChildren,
}: PageProps) {
    const { data, setData, patch, processing, errors } = useForm<AreaFormData>({
        name: area.name,
        type: area.type,
        description: area.description ?? '',
        size_sqm: area.size_sqm ?? '',
        sun_exposure: area.sun_exposure ?? '',
        soil_type: area.soil_type ?? '',
        moisture_level: area.moisture_level ?? '',
        parent_id: area.parent_id ? String(area.parent_id) : '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(update({ garden, area }).url);
    };

    return (
        <>
            <Head title={`${area.name} bearbeiten`} />

            <div className="p-4 md:p-6">
                <PageHeader title="Bereich bearbeiten" className="mb-6" />

                <div className="max-w-2xl">
                    <AreaForm
                        data={data}
                        errors={errors}
                        processing={processing}
                        rootAreas={rootAreas}
                        hasChildren={hasChildren}
                        setData={setData}
                        onSubmit={submit}
                        submitLabel="Änderungen speichern"
                        cancelHref={show({ garden, area }).url}
                    />
                </div>
            </div>
        </>
    );
}

AreasEdit.layout = (props: { garden?: Garden; area?: Area }) => ({
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
        ...(props.garden && props.area
            ? [
                  {
                      title: props.area.name,
                      href: show({
                          garden: props.garden,
                          area: props.area,
                      }).url,
                  },
                  { title: 'Bearbeiten', href: '#' },
              ]
            : []),
    ],
});
