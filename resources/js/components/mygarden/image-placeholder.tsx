import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { cn } from '@/lib/utils';

type Props = {
    aspectRatio?: string;
    label?: string;
    className?: string;
};

export function ImagePlaceholder({
    aspectRatio = '4/3',
    label,
    className,
}: Props) {
    return (
        <div
            className={cn('relative overflow-hidden rounded-lg border border-border', className)}
            style={{ aspectRatio }}
        >
            <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/10" />
            {label && (
                <div className="absolute inset-0 flex items-center justify-center">
                    <span className="text-xs text-muted-foreground">{label}</span>
                </div>
            )}
        </div>
    );
}
