import { show } from '@/actions/App/Http/Controllers/Gardens/GardenImageController';
import type { Garden, GardenImage } from '@/types/garden';

type Props = {
    garden: Garden;
    image: GardenImage;
};

export function GardenCoverImage({ garden, image }: Props) {
    const url = show({ garden: garden.id, image: image.id }).url;

    return (
        <div className="overflow-hidden rounded-lg border border-border bg-muted">
            <img
                src={url}
                alt={garden.name}
                loading="eager"
                className="aspect-video w-full object-cover"
            />
        </div>
    );
}
