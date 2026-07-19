<?php

namespace App\Actions\Plants;

use App\Enums\CatalogStatus;
use App\Exceptions\CatalogActionException;
use App\Models\PlantVariety;

class WithdrawPlantVarietySubmission
{
    public function handle(PlantVariety $variety): void
    {
        if ($variety->catalog_status !== CatalogStatus::PendingReview) {
            throw new CatalogActionException('Nur eingereichte Einträge können zurückgezogen werden.');
        }

        $variety->forceFill(['catalog_status' => CatalogStatus::Private->value])->save();
    }
}
