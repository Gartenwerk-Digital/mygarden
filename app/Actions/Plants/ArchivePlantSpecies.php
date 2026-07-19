<?php

namespace App\Actions\Plants;

use App\Exceptions\CatalogActionException;
use App\Models\PlantSpecies;

class ArchivePlantSpecies
{
    public function handle(PlantSpecies $species): void
    {
        if ($species->varieties()->exists()) {
            throw new CatalogActionException('Eine Pflanzenart mit vorhandenen Sorten kann nicht archiviert werden.');
        }

        if ($species->archived_at === null) {
            $species->forceFill(['archived_at' => now()])->save();
        }
    }
}
