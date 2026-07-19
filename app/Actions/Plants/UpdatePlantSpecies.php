<?php

namespace App\Actions\Plants;

use App\Models\PlantSpecies;

class UpdatePlantSpecies
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(PlantSpecies $species, array $validated): PlantSpecies
    {
        $species->update($validated);

        return $species;
    }
}
