<?php

namespace App\Actions\Plants;

use App\Enums\CatalogOrigin;
use App\Models\PlantSpecies;
use App\Models\User;

class CreatePlantSpecies
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, array $validated): PlantSpecies
    {
        /** @var PlantSpecies $species */
        $species = PlantSpecies::create([
            ...$validated,
            'origin' => CatalogOrigin::User->value,
            'created_by' => $user->id,
        ]);

        return $species;
    }
}
