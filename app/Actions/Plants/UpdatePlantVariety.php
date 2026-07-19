<?php

namespace App\Actions\Plants;

use App\Models\PlantVariety;

class UpdatePlantVariety
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(PlantVariety $variety, array $validated): PlantVariety
    {
        $variety->update($validated);

        return $variety;
    }
}
