import { Head } from '@inertiajs/react';
import { AlertCircle, Info, Leaf, Loader2, Sprout } from 'lucide-react';
import { useState } from 'react';

import { EmptyState } from '@/components/mygarden/empty-state';
import { FormSection } from '@/components/mygarden/form-section';
import { ImagePlaceholder } from '@/components/mygarden/image-placeholder';
import { PageHeader } from '@/components/mygarden/page-header';
import { StatusBadge } from '@/components/mygarden/status-badge';
import { VisibilityBadge } from '@/components/mygarden/visibility-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';

function Section({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="space-y-4">
            <h2 className="border-b border-border pb-2 text-lg font-semibold text-foreground">
                {title}
            </h2>
            {children}
        </section>
    );
}

function ColorSwatch({
    name,
    className,
    textClass = 'text-foreground',
}: {
    name: string;
    className: string;
    textClass?: string;
}) {
    return (
        <div className="flex flex-col gap-1">
            <div
                className={`h-12 rounded-md border border-border ${className}`}
            />
            <span className={`text-xs ${textClass}`}>{name}</span>
        </div>
    );
}

function Textarea({ className, ...props }: React.ComponentProps<'textarea'>) {
    return (
        <textarea
            className={[
                'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                className,
            ]
                .filter(Boolean)
                .join(' ')}
            {...props}
        />
    );
}

export default function DesignSystem() {
    const [checked, setChecked] = useState(false);
    const [switchOn, setSwitchOn] = useState(false);

    return (
        <>
            <Head title="Design System" />
            <div
                className="mx-auto space-y-16 px-6 py-12"
                style={{ maxWidth: 'var(--container-max)' }}
            >
                <div>
                    <h1 className="text-3xl font-semibold text-foreground">
                        myGarden Design System
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        Lokale Referenzseite · nur in der lokalen Umgebung
                        verfügbar
                    </p>
                </div>

                {/* Colours */}
                <Section title="Farben – Semantisch">
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6">
                        <ColorSwatch
                            name="background"
                            className="bg-background"
                        />
                        <ColorSwatch
                            name="foreground"
                            className="bg-foreground"
                        />
                        <ColorSwatch name="card" className="bg-card" />
                        <ColorSwatch name="primary" className="bg-primary" />
                        <ColorSwatch
                            name="secondary"
                            className="bg-secondary"
                        />
                        <ColorSwatch name="muted" className="bg-muted" />
                        <ColorSwatch name="accent" className="bg-accent" />
                        <ColorSwatch
                            name="destructive"
                            className="bg-destructive"
                        />
                        <ColorSwatch name="success" className="bg-success" />
                        <ColorSwatch name="warning" className="bg-warning" />
                        <ColorSwatch name="border" className="bg-border" />
                        <ColorSwatch name="ring" className="bg-ring" />
                    </div>
                </Section>

                <Section title="Farben – Charts und Sidebar">
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                        <ColorSwatch name="chart-1" className="bg-chart-1" />
                        <ColorSwatch name="chart-2" className="bg-chart-2" />
                        <ColorSwatch name="chart-3" className="bg-chart-3" />
                        <ColorSwatch name="chart-4" className="bg-chart-4" />
                        <ColorSwatch name="chart-5" className="bg-chart-5" />
                    </div>
                    <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <ColorSwatch name="sidebar" className="bg-sidebar" />
                        <ColorSwatch
                            name="sidebar-primary"
                            className="bg-sidebar-primary"
                        />
                        <ColorSwatch
                            name="sidebar-accent"
                            className="bg-sidebar-accent"
                        />
                        <ColorSwatch
                            name="sidebar-border"
                            className="bg-sidebar-border"
                        />
                    </div>
                </Section>

                {/* Typography */}
                <Section title="Typografie">
                    <div className="space-y-4">
                        <div>
                            <p className="mb-1 text-xs text-muted-foreground">
                                font-sans (Work Sans) – Produkt-UI
                            </p>
                            <p className="font-sans text-3xl font-semibold">
                                Mein Garten im Sommer
                            </p>
                            <p className="font-sans text-base">
                                Rosen, Lavendel und wilde Kräuter wachsen
                                nebeneinander. Der Garten ist ein Ort der Ruhe.
                            </p>
                            <p className="font-sans text-sm text-muted-foreground">
                                Kleine Beschreibung · 14px · muted-foreground
                            </p>
                        </div>
                        <Separator />
                        <div>
                            <p className="mb-1 text-xs text-muted-foreground">
                                font-serif (Source Serif 4) – nur Marketing/Hero
                            </p>
                            <p className="font-serif text-3xl font-medium">
                                Natürlich & hochwertig
                            </p>
                        </div>
                        <Separator />
                        <div>
                            <p className="mb-1 text-xs text-muted-foreground">
                                font-mono (JetBrains Mono) – Messwerte, IDs
                            </p>
                            <p className="font-mono text-sm">
                                pH: 6.8 · N: 42 mg/l · P: 18 mg/l · K: 95 mg/l
                            </p>
                        </div>
                        <Separator />
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                            {(
                                [
                                    ['text-xs', '12px'],
                                    ['text-sm', '14px'],
                                    ['text-base', '16px'],
                                    ['text-lg', '18px'],
                                    ['text-xl', '20px'],
                                    ['text-2xl', '24px'],
                                    ['text-3xl', '30px'],
                                    ['text-4xl', '36px'],
                                ] as const
                            ).map(([size, px]) => (
                                <div key={size}>
                                    <p className={`${size} font-sans`}>Ag</p>
                                    <p className="text-xs text-muted-foreground">
                                        {size} · {px}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </Section>

                {/* Radii */}
                <Section title="Radien">
                    <div className="flex flex-wrap gap-4">
                        {(
                            [
                                ['rounded-sm', 'radius-sm'],
                                ['rounded-md', 'radius-md'],
                                ['rounded-lg', 'radius-lg / radius'],
                                ['rounded-xl', 'radius-xl'],
                                ['rounded-full', 'radius-full'],
                            ] as const
                        ).map(([cls, name]) => (
                            <div
                                key={cls}
                                className="flex flex-col items-center gap-2"
                            >
                                <div
                                    className={`h-16 w-16 bg-primary ${cls}`}
                                />
                                <span className="text-xs text-muted-foreground">
                                    {name}
                                </span>
                            </div>
                        ))}
                    </div>
                </Section>

                {/* Shadows */}
                <Section title="Schatten">
                    <div className="flex flex-wrap gap-6">
                        {(
                            [
                                '--shadow-sm',
                                '--shadow-md',
                                '--shadow-lg',
                            ] as const
                        ).map((v) => (
                            <div
                                key={v}
                                className="flex flex-col items-center gap-2"
                            >
                                <div
                                    className="h-20 w-32 rounded-lg bg-card"
                                    style={{ boxShadow: `var(${v})` }}
                                />
                                <span className="text-xs text-muted-foreground">
                                    {v}
                                </span>
                            </div>
                        ))}
                    </div>
                </Section>

                {/* Motion */}
                <Section title="Bewegung">
                    <div className="flex gap-4 text-sm text-muted-foreground">
                        <div>
                            <code className="font-mono text-xs">
                                --duration-fast
                            </code>
                            : 120ms
                        </div>
                        <div>
                            <code className="font-mono text-xs">
                                --duration-normal
                            </code>
                            : 200ms
                        </div>
                        <div>
                            <code className="font-mono text-xs">
                                --ease-standard
                            </code>
                            : cubic-bezier(0.2, 0.6, 0.2, 1)
                        </div>
                    </div>
                </Section>

                {/* Buttons */}
                <Section title="Buttons">
                    <div className="flex flex-wrap gap-3">
                        <Button>Primary</Button>
                        <Button variant="secondary">Secondary</Button>
                        <Button variant="outline">Outline</Button>
                        <Button variant="ghost">Ghost</Button>
                        <Button variant="destructive">Destructive</Button>
                        <Button variant="link">Link</Button>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Button size="sm">Small</Button>
                        <Button size="default">Default</Button>
                        <Button size="lg">Large</Button>
                        <Button size="icon">
                            <Leaf className="h-4 w-4" />
                        </Button>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Button disabled>Disabled</Button>
                        <Button variant="secondary" disabled>
                            Disabled
                        </Button>
                        <Button>
                            <Loader2 className="h-4 w-4 animate-spin" />
                            Wird geladen…
                        </Button>
                    </div>
                </Section>

                {/* Form Controls */}
                <Section title="Eingabefelder">
                    <div className="grid max-w-md gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="ds-input">Bezeichnung</Label>
                            <Input
                                id="ds-input"
                                placeholder="z.B. Hochbeet Süd"
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="ds-input-error">Mit Fehler</Label>
                            <Input
                                id="ds-input-error"
                                placeholder="Pflichtfeld"
                                aria-invalid
                            />
                            <p className="text-sm text-destructive">
                                Dieses Feld ist erforderlich.
                            </p>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="ds-input-disabled">
                                Deaktiviert
                            </Label>
                            <Input
                                id="ds-input-disabled"
                                placeholder="Nicht bearbeitbar"
                                disabled
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="ds-textarea">Beschreibung</Label>
                            <Textarea
                                id="ds-textarea"
                                placeholder="Beschreibe diesen Bereich…"
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="ds-select">Kategorie</Label>
                            <Select>
                                <SelectTrigger id="ds-select">
                                    <SelectValue placeholder="Wähle eine Kategorie" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="bed">Beet</SelectItem>
                                    <SelectItem value="lawn">
                                        Rasenfläche
                                    </SelectItem>
                                    <SelectItem value="terrace">
                                        Terrasse
                                    </SelectItem>
                                    <SelectItem value="path">Weg</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="ds-checkbox"
                                checked={checked}
                                onCheckedChange={(v) => setChecked(v === true)}
                            />
                            <Label htmlFor="ds-checkbox">
                                Garten ist öffentlich sichtbar
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Switch
                                id="ds-switch"
                                checked={switchOn}
                                onCheckedChange={setSwitchOn}
                            />
                            <Label htmlFor="ds-switch">
                                Benachrichtigungen aktivieren
                            </Label>
                        </div>
                    </div>
                </Section>

                {/* Badges */}
                <Section title="Badges">
                    <div className="flex flex-wrap gap-2">
                        <Badge>Standard</Badge>
                        <Badge variant="secondary">Sekundär</Badge>
                        <Badge variant="outline">Umriss</Badge>
                        <Badge variant="destructive">Fehler</Badge>
                    </div>
                </Section>

                {/* Alerts */}
                <Section title="Alerts">
                    <div className="max-w-lg space-y-3">
                        <Alert>
                            <Info className="h-4 w-4" />
                            <AlertTitle>Hinweis</AlertTitle>
                            <AlertDescription>
                                Das ist ein informativer Hinweis.
                            </AlertDescription>
                        </Alert>
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertTitle>Fehler</AlertTitle>
                            <AlertDescription>
                                Etwas ist schiefgelaufen. Bitte versuche es
                                erneut.
                            </AlertDescription>
                        </Alert>
                    </div>
                </Section>

                {/* Card */}
                <Section title="Cards">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Hochbeet Süd</CardTitle>
                                <CardDescription>
                                    Sonnige Lage, 1,2 × 0,8 m
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ImagePlaceholder
                                    aspectRatio="16/9"
                                    label="Foto hinzufügen"
                                />
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>Kräuterbeet</CardTitle>
                                <CardDescription>
                                    Halbschatten, gemischte Bepflanzung
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ImagePlaceholder aspectRatio="16/9" />
                            </CardContent>
                        </Card>
                    </div>
                </Section>

                {/* Avatar and Skeleton */}
                <Section title="Avatar und Skeleton">
                    <div className="flex items-center gap-4">
                        <Avatar>
                            <AvatarImage src="" alt="Max Mustermann" />
                            <AvatarFallback>MM</AvatarFallback>
                        </Avatar>
                        <Avatar className="h-12 w-12">
                            <AvatarFallback>CG</AvatarFallback>
                        </Avatar>
                    </div>
                    <div className="mt-4 flex items-center gap-4">
                        <Skeleton className="h-10 w-10 rounded-full" />
                        <div className="space-y-2">
                            <Skeleton className="h-4 w-40" />
                            <Skeleton className="h-4 w-28" />
                        </div>
                    </div>
                    <div className="mt-4">
                        <Spinner className="h-6 w-6" />
                    </div>
                </Section>

                {/* myGarden Components */}
                <Section title="myGarden – PageHeader">
                    <div className="rounded-lg border border-border p-6">
                        <PageHeader
                            title="Meine Gärten"
                            description="Verwalte und dokumentiere deine Gärten."
                            actions={
                                <>
                                    <Button variant="outline" size="sm">
                                        Importieren
                                    </Button>
                                    <Button size="sm">
                                        <Sprout className="h-4 w-4" />
                                        Neuer Garten
                                    </Button>
                                </>
                            }
                        />
                    </div>
                </Section>

                <Section title="myGarden – EmptyState">
                    <div className="rounded-lg border border-border">
                        <EmptyState
                            icon={Sprout}
                            title="Noch kein Garten angelegt"
                            description="Erstelle deinen ersten Garten, um loszulegen."
                            action={<Button size="sm">Garten anlegen</Button>}
                        />
                    </div>
                </Section>

                <Section title="myGarden – FormSection">
                    <div className="rounded-lg border border-border px-6">
                        <FormSection
                            title="Allgemeine Infos"
                            description="Grundlegende Informationen zu deinem Garten."
                        >
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="garden-name">Name</Label>
                                    <Input
                                        id="garden-name"
                                        placeholder="Mein Garten"
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="garden-desc">
                                        Beschreibung
                                    </Label>
                                    <Textarea
                                        id="garden-desc"
                                        placeholder="Optional…"
                                    />
                                </div>
                            </div>
                        </FormSection>
                    </div>
                </Section>

                <Section title="myGarden – StatusBadge und VisibilityBadge">
                    <div className="flex flex-wrap gap-3">
                        <StatusBadge variant="default" label="Aktiv" />
                        <StatusBadge variant="secondary" label="Inaktiv" />
                        <StatusBadge variant="outline" label="Archiviert" />
                        <StatusBadge variant="destructive" label="Fehler" />
                    </div>
                    <div className="mt-3 flex flex-wrap gap-3">
                        <VisibilityBadge visibility="private" />
                        <VisibilityBadge visibility="connections" />
                        <VisibilityBadge visibility="unlisted" />
                        <VisibilityBadge visibility="public" />
                    </div>
                </Section>

                <Section title="myGarden – ImagePlaceholder">
                    <div className="grid max-w-lg grid-cols-3 gap-4">
                        <ImagePlaceholder aspectRatio="4/3" label="4:3" />
                        <ImagePlaceholder aspectRatio="1/1" label="1:1" />
                        <ImagePlaceholder aspectRatio="16/9" label="16:9" />
                    </div>
                </Section>

                {/* Interaction States */}
                <Section title="Interaktionszustände">
                    <div className="flex flex-wrap gap-3">
                        <Button>Normal</Button>
                        <Button className="ring-2 ring-ring ring-offset-2 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            Focus
                        </Button>
                        <Button disabled>Disabled</Button>
                        <Button>
                            <Loader2 className="h-4 w-4 animate-spin" />
                            Loading
                        </Button>
                        <Button variant="destructive">Error</Button>
                    </div>
                    <div className="mt-4 flex flex-wrap gap-3">
                        <Button variant="outline">Outline</Button>
                        <Button variant="ghost">Ghost</Button>
                        <Button variant="secondary">Secondary</Button>
                    </div>
                </Section>
            </div>
        </>
    );
}
