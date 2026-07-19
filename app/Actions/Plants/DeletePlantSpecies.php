<?php

namespace App\Actions\Plants;

use App\Models\PlantSpecies;
use RuntimeException;

class DeletePlantSpecies
{
    public function handle(PlantSpecies $species): void
    {
        if ($species->varieties()->exists()) {
            throw new RuntimeException('Eine Pflanzenart mit vorhandenen Sorten kann nicht gelöscht werden.');
        }

        $species->delete();
    }
}
