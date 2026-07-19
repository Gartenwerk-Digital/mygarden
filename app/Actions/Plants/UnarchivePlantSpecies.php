<?php

namespace App\Actions\Plants;

use App\Models\PlantSpecies;

class UnarchivePlantSpecies
{
    public function handle(PlantSpecies $species): void
    {
        if ($species->archived_at !== null) {
            $species->forceFill(['archived_at' => null])->save();
        }
    }
}
