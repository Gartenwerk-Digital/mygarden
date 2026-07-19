<?php

namespace App\Actions\Plants;

use App\Enums\CatalogOrigin;
use App\Enums\CatalogStatus;
use App\Models\PlantSpecies;
use App\Models\User;

class CreatePlantSpecies
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, array $validated): PlantSpecies
    {
        $species = new PlantSpecies($validated);
        $species->forceFill([
            'origin' => CatalogOrigin::User->value,
            'created_by' => $user->id,
            'catalog_status' => CatalogStatus::Private->value,
        ])->save();

        return $species;
    }
}
