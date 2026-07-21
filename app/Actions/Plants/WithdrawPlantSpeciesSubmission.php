<?php

namespace App\Actions\Plants;

use App\Enums\CatalogStatus;
use App\Exceptions\CatalogActionException;
use App\Models\PlantSpecies;

class WithdrawPlantSpeciesSubmission
{
    public function handle(PlantSpecies $species): void
    {
        if ($species->catalog_status !== CatalogStatus::PendingReview) {
            throw new CatalogActionException('Nur eingereichte Einträge können zurückgezogen werden.');
        }

        $species->forceFill([
            'catalog_status' => CatalogStatus::Private->value,
            'submitted_at' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'moderation_note' => null,
            'duplicate_of_id' => null,
        ])->save();
    }
}
