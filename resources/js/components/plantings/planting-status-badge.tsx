import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { PlantingStatus } from '@/types/planting';
import { plantingStatusLabels } from '@/types/planting';

const statusVariant: Record<
    PlantingStatus,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    planted: 'default',
    planned: 'secondary',
    removed: 'outline',
};

type Props = {
    status: PlantingStatus;
    className?: string;
};

export function PlantingStatusBadge({ status, className }: Props) {
    return (
        <Badge variant={statusVariant[status]} className={cn(className)}>
            {plantingStatusLabels[status]}
        </Badge>
    );
}
