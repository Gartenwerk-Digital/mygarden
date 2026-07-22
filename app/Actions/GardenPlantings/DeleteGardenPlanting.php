<?php

namespace App\Actions\GardenPlantings;

use App\Models\GardenPlanting;

class DeleteGardenPlanting
{
    public function handle(GardenPlanting $planting): void
    {
        $planting->delete();
    }
}
