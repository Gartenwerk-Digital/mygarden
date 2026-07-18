import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

type Props = {
    title: string;
    description?: string;
    children: ReactNode;
    className?: string;
};

export function FormSection({
    title,
    description,
    children,
    className,
}: Props) {
    return (
        <div
            className={cn(
                'grid grid-cols-1 gap-6 border-b border-border py-6 last:border-b-0 md:grid-cols-3',
                className,
            )}
        >
            <div className="space-y-1">
                <h2 className="text-sm font-semibold text-foreground">
                    {title}
                </h2>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            <div className="md:col-span-2">{children}</div>
        </div>
    );
}
