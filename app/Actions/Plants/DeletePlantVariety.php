<?php

namespace App\Actions\Plants;

use App\Models\PlantVariety;

class DeletePlantVariety
{
    public function handle(PlantVariety $variety): void
    {
        $variety->delete();
    }
}
