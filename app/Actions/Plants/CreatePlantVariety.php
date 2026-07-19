<?php

namespace App\Actions\Plants;

use App\Enums\CatalogOrigin;
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
        /** @var PlantVariety $variety */
        $variety = $species->varieties()->create([
            ...$validated,
            'origin' => CatalogOrigin::User->value,
            'created_by' => $user->id,
        ]);

        return $variety;
    }
}
