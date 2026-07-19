import { Badge } from '@/components/ui/badge';
import type { CatalogStatus } from '@/types/plant';

const statusConfig: Record<
    CatalogStatus,
    {
        label: string;
        variant: 'default' | 'secondary' | 'outline' | 'destructive';
    }
> = {
    private: { label: 'Privat', variant: 'secondary' },
    pending_review: { label: 'In Prüfung', variant: 'outline' },
    approved: { label: 'Freigegeben', variant: 'default' },
    rejected: { label: 'Abgelehnt', variant: 'destructive' },
};

type Props = {
    status: CatalogStatus;
    className?: string;
};

export function CatalogStatusBadge({ status, className }: Props) {
    const { label, variant } = statusConfig[status];

    return (
        <Badge variant={variant} className={className}>
            {label}
        </Badge>
    );
}
