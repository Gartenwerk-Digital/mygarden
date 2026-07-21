import { Head, router, useForm } from '@inertiajs/react';
import { CheckCircle, XCircle } from 'lucide-react';
import { useState } from 'react';

import {
    approveVariety,
    index,
    rejectVariety,
    showVariety,
} from '@/actions/App/Http/Controllers/Plants/PlantModerationController';
import { PageHeader } from '@/components/mygarden/page-header';
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
import type { PlantSpecies, PlantVariety } from '@/types/plant';

type ApprovedVarietySummary = { id: number; name: string };

type PageProps = {
    plantSpecies: PlantSpecies;
    variety: PlantVariety;
    approvedVarieties: ApprovedVarietySummary[];
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

export default function ModerationPlantsVarietyShow({
    plantSpecies,
    variety,
    approvedVarieties,
}: PageProps) {
    const [rejectDialogOpen, setRejectDialogOpen] = useState(false);

    const rejectForm = useForm({
        moderation_note: '',
        duplicate_of_id: '',
    });

    const handleApprove = () => {
        router.patch(approveVariety({ plantSpecies, variety }).url);
    };

    const handleRejectSubmit = () => {
        rejectForm.patch(rejectVariety({ plantSpecies, variety }).url, {
            onSuccess: () => setRejectDialogOpen(false),
        });
    };

    return (
        <>
            <Head title={`Prüfung: ${variety.name}`} />

            <div className="p-4 md:p-6">
                <PageHeader
                    title={`'${variety.name}'`}
                    description={`Sorte von ${plantSpecies.common_name}`}
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
                                {variety.creator
                                    ? variety.creator.username
                                    : 'Gelöschter Nutzer'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Eingereicht am
                            </dt>
                            <dd>{formatDate(variety.submitted_at)}</dd>
                        </div>
                        {variety.reviewer && (
                            <>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Zuletzt geprüft von
                                    </dt>
                                    <dd>{variety.reviewer.username}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Geprüft am
                                    </dt>
                                    <dd>{formatDate(variety.reviewed_at)}</dd>
                                </div>
                            </>
                        )}
                    </dl>
                </div>

                <div className="max-w-2xl space-y-4">
                    {variety.breeder && (
                        <p className="text-sm text-muted-foreground">
                            Züchter: {variety.breeder}
                        </p>
                    )}
                    {variety.year_introduced && (
                        <p className="text-sm text-muted-foreground">
                            Eingeführt: {variety.year_introduced}
                        </p>
                    )}
                    {variety.description && (
                        <p className="text-sm leading-relaxed text-foreground">
                            {variety.description}
                        </p>
                    )}
                </div>
            </div>

            <Dialog open={rejectDialogOpen} onOpenChange={setRejectDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Sorte ablehnen</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4">
                        <p className="text-sm text-muted-foreground">
                            Bitte gib eine Begründung an oder wähle eine
                            ähnliche Sorte. Mindestens eines der beiden Felder
                            muss ausgefüllt sein.
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

                        {approvedVarieties.length > 0 && (
                            <div className="space-y-2">
                                <Label htmlFor="duplicate_of_id">
                                    Ähnliche Sorte
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
                                        <SelectValue placeholder="Keine auswählen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Keine auswählen
                                        </SelectItem>
                                        {approvedVarieties.map((v) => (
                                            <SelectItem
                                                key={v.id}
                                                value={String(v.id)}
                                            >
                                                '{v.name}'
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

ModerationPlantsVarietyShow.layout = (props: {
    plantSpecies?: PlantSpecies;
    variety?: PlantVariety;
}) => ({
    breadcrumbs: [
        { title: 'Moderierung', href: index().url },
        ...(props.plantSpecies && props.variety
            ? [
                  {
                      title: `'${props.variety.name}'`,
                      href: showVariety({
                          plantSpecies: props.plantSpecies,
                          variety: props.variety,
                      }).url,
                  },
              ]
            : []),
    ],
});
