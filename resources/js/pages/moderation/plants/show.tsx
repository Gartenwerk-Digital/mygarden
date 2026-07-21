import { Head, router, useForm } from '@inertiajs/react';
import { CheckCircle, XCircle } from 'lucide-react';
import { useState } from 'react';

import {
    approveSpecies,
    index,
    rejectSpecies,
    showSpecies,
} from '@/actions/App/Http/Controllers/Plants/PlantModerationController';
import { PageHeader } from '@/components/mygarden/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { EnumOption, PlantSpecies } from '@/types/plant';

type ApprovedSpeciesSummary = { id: number; common_name: string };

type PageProps = {
    plantSpecies: PlantSpecies;
    approvedSpecies: ApprovedSpeciesSummary[];
    categories: EnumOption[];
    lifecycles: EnumOption[];
    sunExposures: EnumOption[];
    soilTypes: EnumOption[];
    moistureLevels: EnumOption[];
};

function formatDate(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function ModerationPlantsShow({
    plantSpecies,
    approvedSpecies,
    categories,
    lifecycles,
    sunExposures,
    soilTypes,
    moistureLevels,
}: PageProps) {
    const [rejectDialogOpen, setRejectDialogOpen] = useState(false);

    const rejectForm = useForm({
        moderation_note: '',
        duplicate_of_id: '',
    });

    const labelFor = (
        options: EnumOption[],
        value: string | null | undefined,
    ) =>
        value ? (options.find((o) => o.value === value)?.label ?? value) : null;

    const handleApprove = () => {
        router.patch(approveSpecies(plantSpecies).url);
    };

    const handleRejectSubmit = () => {
        rejectForm.patch(rejectSpecies(plantSpecies).url, {
            onSuccess: () => setRejectDialogOpen(false),
        });
    };

    return (
        <>
            <Head title={`Prüfung: ${plantSpecies.common_name}`} />

            <div className="p-4 md:p-6">
                <PageHeader
                    title={plantSpecies.common_name}
                    description="Eingereichten Eintrag prüfen"
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                onClick={() => setRejectDialogOpen(true)}
                            >
                                <XCircle className="mr-2 size-4" />
                                Ablehnen
                            </Button>
                            <Button size="sm" onClick={handleApprove}>
                                <CheckCircle className="mr-2 size-4" />
                                Freigeben
                            </Button>
                        </div>
                    }
                />

                <div className="mb-6 rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm">
                    <dl className="grid grid-cols-2 gap-x-4 gap-y-1 sm:grid-cols-4">
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Eingereicht von
                            </dt>
                            <dd className="font-medium">
                                {plantSpecies.creator
                                    ? plantSpecies.creator.username
                                    : 'Gelöschter Nutzer'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Eingereicht am
                            </dt>
                            <dd>{formatDate(plantSpecies.submitted_at)}</dd>
                        </div>
                        {plantSpecies.reviewer && (
                            <>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Zuletzt geprüft von
                                    </dt>
                                    <dd>{plantSpecies.reviewer.username}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Geprüft am
                                    </dt>
                                    <dd>
                                        {formatDate(plantSpecies.reviewed_at)}
                                    </dd>
                                </div>
                            </>
                        )}
                    </dl>
                </div>

                <div className="max-w-2xl space-y-6">
                    <div className="flex flex-wrap items-center gap-2">
                        {labelFor(categories, plantSpecies.category) && (
                            <Badge variant="secondary">
                                {labelFor(categories, plantSpecies.category)}
                            </Badge>
                        )}
                        {plantSpecies.lifecycle && (
                            <Badge variant="outline">
                                {labelFor(lifecycles, plantSpecies.lifecycle)}
                            </Badge>
                        )}
                    </div>

                    {plantSpecies.botanical_name && (
                        <p className="text-sm text-muted-foreground italic">
                            {plantSpecies.botanical_name}
                        </p>
                    )}

                    {plantSpecies.alternate_name && (
                        <p className="text-sm text-muted-foreground">
                            Auch bekannt als: {plantSpecies.alternate_name}
                        </p>
                    )}

                    {plantSpecies.description && (
                        <p className="text-sm leading-relaxed text-foreground">
                            {plantSpecies.description}
                        </p>
                    )}

                    {(plantSpecies.sun_exposures?.length ||
                        plantSpecies.soil_types?.length ||
                        plantSpecies.moisture_levels?.length ||
                        plantSpecies.height_min_cm ||
                        plantSpecies.height_max_cm ||
                        plantSpecies.hardiness_note) && (
                        <div className="space-y-2">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Standortansprüche
                            </p>
                            <div className="flex flex-wrap gap-2">
                                {plantSpecies.sun_exposures?.map((v) => (
                                    <Badge key={v} variant="outline">
                                        {labelFor(sunExposures, v)}
                                    </Badge>
                                ))}
                                {plantSpecies.soil_types?.map((v) => (
                                    <Badge key={v} variant="outline">
                                        {labelFor(soilTypes, v)}
                                    </Badge>
                                ))}
                                {plantSpecies.moisture_levels?.map((v) => (
                                    <Badge key={v} variant="outline">
                                        {labelFor(moistureLevels, v)}
                                    </Badge>
                                ))}
                                {(plantSpecies.height_min_cm ||
                                    plantSpecies.height_max_cm) && (
                                    <Badge variant="outline">
                                        {plantSpecies.height_min_cm &&
                                        plantSpecies.height_max_cm
                                            ? `${plantSpecies.height_min_cm}–${plantSpecies.height_max_cm} cm`
                                            : plantSpecies.height_min_cm
                                              ? `ab ${plantSpecies.height_min_cm} cm`
                                              : `bis ${plantSpecies.height_max_cm} cm`}
                                    </Badge>
                                )}
                                {plantSpecies.hardiness_note && (
                                    <Badge variant="outline">
                                        {plantSpecies.hardiness_note}
                                    </Badge>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <Dialog open={rejectDialogOpen} onOpenChange={setRejectDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eintrag ablehnen</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4">
                        <p className="text-sm text-muted-foreground">
                            Bitte gib eine Begründung an oder wähle einen
                            ähnlichen Eintrag. Mindestens eines der beiden
                            Felder muss ausgefüllt sein.
                        </p>

                        <div className="space-y-2">
                            <Label htmlFor="moderation_note">Begründung</Label>
                            <textarea
                                id="moderation_note"
                                value={rejectForm.data.moderation_note}
                                onChange={(e) =>
                                    rejectForm.setData(
                                        'moderation_note',
                                        e.target.value,
                                    )
                                }
                                rows={4}
                                maxLength={1000}
                                placeholder="Hinweis für den Ersteller …"
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            {rejectForm.errors.moderation_note && (
                                <p className="text-xs text-destructive">
                                    {rejectForm.errors.moderation_note}
                                </p>
                            )}
                        </div>

                        {approvedSpecies.length > 0 && (
                            <div className="space-y-2">
                                <Label htmlFor="duplicate_of_id">
                                    Ähnlicher Eintrag
                                </Label>
                                <Select
                                    value={rejectForm.data.duplicate_of_id}
                                    onValueChange={(v) =>
                                        rejectForm.setData(
                                            'duplicate_of_id',
                                            v === 'none' ? '' : v,
                                        )
                                    }
                                >
                                    <SelectTrigger id="duplicate_of_id">
                                        <SelectValue placeholder="Keinen auswählen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Keinen auswählen
                                        </SelectItem>
                                        {approvedSpecies.map((s) => (
                                            <SelectItem
                                                key={s.id}
                                                value={String(s.id)}
                                            >
                                                {s.common_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {rejectForm.errors.duplicate_of_id && (
                                    <p className="text-xs text-destructive">
                                        {rejectForm.errors.duplicate_of_id}
                                    </p>
                                )}
                            </div>
                        )}
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setRejectDialogOpen(false)}
                        >
                            Abbrechen
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={rejectForm.processing}
                            onClick={handleRejectSubmit}
                        >
                            Ablehnen
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

ModerationPlantsShow.layout = (props: { plantSpecies?: PlantSpecies }) => ({
    breadcrumbs: [
        { title: 'Moderierung', href: index().url },
        ...(props.plantSpecies
            ? [
                  {
                      title: props.plantSpecies.common_name,
                      href: showSpecies(props.plantSpecies).url,
                  },
              ]
            : []),
    ],
});
