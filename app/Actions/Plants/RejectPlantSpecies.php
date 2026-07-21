<?php

namespace App\Actions\Plants;

use App\Enums\CatalogStatus;
use App\Exceptions\CatalogActionException;
use App\Models\PlantSpecies;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RejectPlantSpecies
{
    public function handle(PlantSpecies $species, User $moderator, ?string $note, ?int $duplicateOfId): void
    {
        $note = $note !== null ? trim($note) : null;
        if ($note === '') {
            $note = null;
        }

        if ($note === null && $duplicateOfId === null) {
            throw new CatalogActionException('Bitte gib eine Begründung an oder wähle einen ähnlichen Eintrag.');
        }

        DB::transaction(function () use ($species, $moderator, $note, $duplicateOfId): void {
            $locked = PlantSpecies::query()->lockForUpdate()->findOrFail($species->id);

            if ($locked->catalog_status !== CatalogStatus::PendingReview) {
                throw new CatalogActionException('Dieser Eintrag wurde bereits bearbeitet oder zurückgezogen.');
            }

            if ($duplicateOfId !== null) {
                $target = PlantSpecies::query()
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
