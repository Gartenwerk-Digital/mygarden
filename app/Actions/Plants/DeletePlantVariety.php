<?php

namespace App\Actions\Plants;

use App\Exceptions\CatalogActionException;
use App\Models\PlantVariety;

class DeletePlantVariety
{
    public function handle(PlantVariety $variety): void
    {
        if ($variety->plantings()->whereNull('deleted_at')->exists()) {
            throw new CatalogActionException('Eine Sorte, die in einem Garten verwendet wird, kann nicht gelöscht werden.');
        }

        $variety->delete();
    }
}
