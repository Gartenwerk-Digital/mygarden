<?php

namespace App\Actions\Plants;

use App\Enums\CatalogOrigin;
use App\Enums\CatalogStatus;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\User;

class CreatePlantVariety
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, PlantSpecies $species, array $validated): PlantVariety
    {
        $variety = new PlantVariety($validated);
        $variety->forceFill([
            'plant_species_id' => $species->id,
            'origin' => CatalogOrigin::User->value,
            'created_by' => $user->id,
            'catalog_status' => CatalogStatus::Private->value,
        ])->save();

        return $variety;
    }
}
