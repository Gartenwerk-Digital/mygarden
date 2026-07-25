import { router } from '@inertiajs/react';
import { Star, StarOff, Trash2 } from 'lucide-react';
import { useState } from 'react';

import {
    destroy,
    setCover,
    show,
    unsetCover,
} from '@/actions/App/Http/Controllers/Gardens/GardenImageController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Garden, GardenImage } from '@/types/garden';

type Props = {
    garden: Garden;
    images: GardenImage[];
};

export function GardenImageGallery({ garden, images }: Props) {
    const [pendingDelete, setPendingDelete] = useState<GardenImage | null>(
        null,
    );
    const [processing, setProcessing] = useState(false);

    if (images.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Noch keine Bilder hochgeladen.
            </p>
        );
    }

    function handleSetCover(image: GardenImage) {
        router.put(
            setCover({ garden: garden.id, image: image.id }).url,
            {},
            { preserveScroll: true },
        );
    }

    function handleUnsetCover() {
        router.delete(unsetCover(garden).url, { preserveScroll: true });
    }

    function confirmDelete() {
        if (!pendingDelete) {
            return;
        }

        setProcessing(true);
        router.delete(
            destroy({ garden: garden.id, image: pendingDelete.id }).url,
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(false);
                    setPendingDelete(null);
                },
            },
        );
    }

    return (
        <>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                {images.map((image) => {
                    const imageUrl = show({
                        garden: garden.id,
                        image: image.id,
                    }).url;

                    return (
                        <div
                            key={image.id}
                            className="group relative overflow-hidden rounded-md border border-border bg-muted"
                        >
                            <img
                                src={imageUrl}
                                alt=""
                                loading="lazy"
                                className="aspect-square w-full object-cover"
                            />

                            {image.is_cover && (
                                <div className="absolute top-2 left-2">
                                    <Badge variant="secondary">Titelbild</Badge>
                                </div>
                            )}

                            <div className="absolute inset-x-0 bottom-0 flex items-center justify-between gap-1 bg-background/85 px-2 py-1.5 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100">
                                {image.is_cover ? (
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={handleUnsetCover}
                                        aria-label="Titelbild entfernen"
                                    >
                                        <StarOff className="size-4" />
                                    </Button>
                                ) : (
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() => handleSetCover(image)}
                                        aria-label="Als Titelbild verwenden"
                                    >
                                        <Star className="size-4" />
                                    </Button>
                                )}

                                <Button
                                    type="button"
                                    size="sm"
                                    variant="destructive"
                                    onClick={() => setPendingDelete(image)}
                                    aria-label="Bild löschen"
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        </div>
                    );
                })}
            </div>

            <Dialog
                open={pendingDelete !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingDelete(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Bild löschen?</DialogTitle>
                        <DialogDescription>
                            Das Bild wird endgültig entfernt. Diese Aktion kann
                            nicht rückgängig gemacht werden.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setPendingDelete(null)}
                            disabled={processing}
                        >
                            Abbrechen
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={confirmDelete}
                            disabled={processing}
                        >
                            {processing ? 'Wird gelöscht...' : 'Löschen'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
