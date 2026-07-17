import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type BadgeVariant = 'default' | 'secondary' | 'outline' | 'destructive';

type Props = {
    variant: BadgeVariant;
    label: string;
    className?: string;
};

export function StatusBadge({ variant, label, className }: Props) {
    return (
        <Badge variant={variant} className={cn(className)}>
            {label}
        </Badge>
    );
}
