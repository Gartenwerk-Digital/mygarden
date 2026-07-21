<?php

namespace App\Actions\Plants;

use App\Enums\CatalogStatus;
use App\Exceptions\CatalogActionException;
use App\Models\PlantVariety;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RejectPlantVariety
{
    public function handle(PlantVariety $variety, User $moderator, ?string $note, ?int $duplicateOfId): void
    {
        $note = $note !== null ? trim($note) : null;
        if ($note === '') {
            $note = null;
        }

        if ($note === null && $duplicateOfId === null) {
            throw new CatalogActionException('Bitte gib eine Begründung an oder wähle einen ähnlichen Eintrag.');
        }

        DB::transaction(function () use ($variety, $moderator, $note, $duplicateOfId): void {
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

            if ($duplicateOfId !== null) {
                $target = PlantVariety::query()
                    ->where('plant_species_id', $locked->plant_species_id)
                    ->where('catalog_status', CatalogStatus::Approved->value)
                    ->whereNull('archived_at')
                    ->whereNull('deleted_at')
                    ->find($duplicateOfId);

                if ($target === null || $target->id === $locked->id) {
                    throw new CatalogActionException('Ungültiger Duplikateintrag.');
                }
            }

            $locked->forceFill([
                'catalog_status' => CatalogStatus::Rejected->value,
                'reviewed_by' => $moderator->id,
                'reviewed_at' => now(),
                'moderation_note' => $note,
                'duplicate_of_id' => $duplicateOfId,
            ])->save();
        });
    }
}
