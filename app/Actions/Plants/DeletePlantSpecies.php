<?php

namespace App\Actions\Plants;

use App\Exceptions\CatalogActionException;
use App\Models\PlantSpecies;

class DeletePlantSpecies
{
    public function handle(PlantSpecies $species): void
    {
        if ($species->varieties()->exists()) {
            throw new CatalogActionException('Eine Pflanzenart mit vorhandenen Sorten kann nicht gelöscht werden.');
        }

        if ($species->plantings()->whereNull('deleted_at')->exists()) {
            throw new CatalogActionException('Eine Pflanzenart, die in einem Garten verwendet wird, kann nicht gelöscht werden.');
        }

        $species->delete();
    }
}
