<?php

namespace App\Actions\Plants;

use App\Enums\CatalogStatus;
use App\Exceptions\CatalogActionException;
use App\Models\PlantVariety;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovePlantVariety
{
    public function handle(PlantVariety $variety, User $moderator): void
    {
        DB::transaction(function () use ($variety, $moderator): void {
            $locked = PlantVariety::query()->lockForUpdate()->findOrFail($variety->id);

            if ($locked->catalog_status !== CatalogStatus::PendingReview) {
                throw new CatalogActionException('Dieser Eintrag wurde bereits bearbeitet oder zurückgezogen.');
            }

            $parentSpecies = $locked->species()->withTrashed()->first();
            if (
                $parentSpecies === null
                || $parentSpecies->catalog_status !== CatalogStatus::Approved
                || $parentSpecies->archived_at !== null
                || $parentSpecies->deleted_at !== null
            ) {
                throw new CatalogActionException('Die zugehörige Pflanzenart ist nicht mehr freigegeben oder aktiv.');
            }

            $locked->forceFill([
                'catalog_status' => CatalogStatus::Approved->value,
                'reviewed_by' => $moderator->id,
                'reviewed_at' => now(),
                'moderation_note' => null,
                'duplicate_of_id' => null,
            ])->save();
        });
    }
}
