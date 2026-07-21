<?php

namespace App\Actions\Plants;

use App\Enums\CatalogStatus;
use App\Exceptions\CatalogActionException;
use App\Models\PlantVariety;

class SubmitPlantVarietyForReview
{
    public function handle(PlantVariety $variety): void
    {
        if (! in_array($variety->catalog_status, [CatalogStatus::Private, CatalogStatus::Rejected], strict: true)) {
            throw new CatalogActionException('Nur private oder abgelehnte Einträge können eingereicht werden.');
        }

        if ($variety->species->catalog_status !== CatalogStatus::Approved) {
            throw new CatalogActionException('Die zugehörige Pflanzenart muss zuerst freigegeben sein, bevor die Sorte eingereicht werden kann.');
        }

        $variety->forceFill([
            'catalog_status' => CatalogStatus::PendingReview->value,
            'submitted_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'moderation_note' => null,
            'duplicate_of_id' => null,
        ])->save();
    }
}
