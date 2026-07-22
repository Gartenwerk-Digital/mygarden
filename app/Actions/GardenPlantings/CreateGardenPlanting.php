<?php

namespace App\Actions\GardenPlantings;

use App\Enums\PlantingStatus;
use App\Exceptions\CatalogActionException;
use App\Models\Area;
use App\Models\Garden;
use App\Models\GardenPlanting;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CreateGardenPlanting
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(Garden $garden, User $owner, array $validated): GardenPlanting
    {
        if ($owner->id !== $garden->user_id) {
            throw new AuthorizationException;
        }

        $speciesId = (int) $validated['plant_species_id'];
        $varietyId = isset($validated['plant_variety_id']) ? (int) $validated['plant_variety_id'] : null;
        $areaId = isset($validated['area_id']) ? (int) $validated['area_id'] : null;

        $this->validateSpecies($owner, $speciesId);
        $this->validateVariety($owner, $speciesId, $varietyId);
        $this->validateArea($garden, $areaId);

        $data = Arr::only($validated, [
            'area_id',
            'plant_species_id',
            'plant_variety_id',
            'status',
            'quantity',
            'quantity_unit',
            'nickname',
            'notes',
            'planted_at',
            'acquired_at',
        ]);

        $planting = $garden->plantings()->create($data);

        if (isset($data['status']) && PlantingStatus::from($data['status']) === PlantingStatus::Removed) {
            $planting->forceFill(['removed_at' => now()])->save();
        }

        return $planting;
    }

    private function validateSpecies(User $owner, int $speciesId): void
    {
        $exists = PlantSpecies::selectableForPlanting($owner)
            ->where('id', $speciesId)
            ->exists();

        if (! $exists) {
            throw new CatalogActionException('Die gewählte Pflanze ist nicht verfügbar oder nicht auswählbar.');
        }
    }

    private function validateVariety(User $owner, int $speciesId, ?int $varietyId): void
    {
        if ($varietyId === null) {
            return;
        }

        $exists = PlantVariety::selectableForPlanting($owner)
            ->where('id', $varietyId)
            ->where('plant_species_id', $speciesId)
            ->exists();

        if (! $exists) {
            throw new CatalogActionException('Die gewählte Sorte ist nicht verfügbar oder gehört nicht zur gewählten Pflanze.');
        }
    }

    private function validateArea(Garden $garden, ?int $areaId): void
    {
        if ($areaId === null) {
            return;
        }

        $valid = Area::where('id', $areaId)
            ->where('garden_id', $garden->id)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'area_id' => __('Der gewählte Bereich ist nicht gültig oder gehört nicht zu diesem Garten.'),
            ]);
        }
    }
}
