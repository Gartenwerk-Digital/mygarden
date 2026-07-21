<?php

namespace App\Actions\Plants;

use App\Enums\CatalogStatus;
use App\Exceptions\CatalogActionException;
use App\Models\PlantSpecies;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovePlantSpecies
{
    public function handle(PlantSpecies $species, User $moderator): void
    {
        DB::transaction(function () use ($species, $moderator): void {
            $locked = PlantSpecies::query()->lockForUpdate()->findOrFail($species->id);

            if ($locked->catalog_status !== CatalogStatus::PendingReview) {
                throw new CatalogActionException('Dieser Eintrag wurde bereits bearbeitet oder zurückgezogen.');
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
