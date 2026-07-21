<?php

namespace App\Actions\Plants;

use App\Enums\CatalogStatus;
use App\Exceptions\CatalogActionException;
use App\Models\PlantSpecies;

class SubmitPlantSpeciesForReview
{
    public function handle(PlantSpecies $species): void
    {
        if (! in_array($species->catalog_status, [CatalogStatus::Private, CatalogStatus::Rejected], strict: true)) {
            throw new CatalogActionException('Nur private oder abgelehnte Einträge können eingereicht werden.');
        }

        $species->forceFill([
            'catalog_status' => CatalogStatus::PendingReview->value,
            'submitted_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'moderation_note' => null,
            'duplicate_of_id' => null,
        ])->save();
    }
}
