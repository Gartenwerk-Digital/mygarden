import { EyeOff, Globe, Lock, Users } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type GardenVisibility = 'private' | 'connections' | 'unlisted' | 'public';

type Props = {
    visibility: GardenVisibility;
    className?: string;
};

const config: Record<
    GardenVisibility,
    { label: string; icon: typeof Lock; variant: 'default' | 'secondary' | 'outline' }
> = {
    private: { label: 'Privat', icon: Lock, variant: 'secondary' },
    connections: { label: 'Kontakte', icon: Users, variant: 'secondary' },
    unlisted: { label: 'Versteckt', icon: EyeOff, variant: 'outline' },
    public: { label: 'Öffentlich', icon: Globe, variant: 'default' },
};

export function VisibilityBadge({ visibility, className }: Props) {
    const { label, icon: Icon, variant } = config[visibility];

    return (
        <Badge variant={variant} className={cn('gap-1', className)}>
            <Icon className="h-3 w-3" />
            {label}
        </Badge>
    );
}
