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

        $species->delete();
    }
}
