import { router } from '@inertiajs/react';
import { useState } from 'react';

import { store } from '@/actions/App/Http/Controllers/Gardens/GardenImageController';
import { ImageUpload } from '@/components/mygarden/image-upload';
import { Button } from '@/components/ui/button';
import type { Garden } from '@/types/garden';

type Props = {
    garden: Garden;
    remainingSlots: number;
};

export function GardenImageUpload({ garden, remainingSlots }: Props) {
    const [files, setFiles] = useState<File[]>([]);
    const [processing, setProcessing] = useState(false);

    const maxFiles = Math.min(5, remainingSlots);

    if (remainingSlots <= 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Das Bildlimit für diesen Garten ist erreicht.
            </p>
        );
    }

    function submit() {
        if (files.length === 0 || processing) {
            return;
        }

        setProcessing(true);
        router.post(
            store(garden).url,
            { images: files },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => setFiles([]),
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <div className="space-y-3">
            <ImageUpload
                files={files}
                onChange={setFiles}
                maxFiles={maxFiles}
                hint={`max. 3 MB je Bild · bis zu ${maxFiles} Bilder auf einmal`}
                disabled={processing}
            />
            {files.length > 0 && (
                <Button
                    type="button"
                    size="sm"
                    onClick={submit}
                    disabled={processing}
                >
                    {processing ? 'Wird hochgeladen...' : 'Hochladen'}
                </Button>
            )}
        </div>
    );
}
