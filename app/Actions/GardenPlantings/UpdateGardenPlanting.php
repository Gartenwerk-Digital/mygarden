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
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class UpdateGardenPlanting
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(GardenPlanting $planting, Garden $garden, User $owner, array $validated): GardenPlanting
    {
        if ($owner->id !== $garden->user_id || $planting->garden_id !== $garden->id) {
            throw new AuthorizationException;
        }

        $newSpeciesId = isset($validated['plant_species_id']) ? (int) $validated['plant_species_id'] : null;
        $newVarietyId = array_key_exists('plant_variety_id', $validated) ? ($validated['plant_variety_id'] !== null ? (int) $validated['plant_variety_id'] : null) : false;
        $newAreaId = array_key_exists('area_id', $validated) ? ($validated['area_id'] !== null ? (int) $validated['area_id'] : null) : false;

        $speciesChanged = $newSpeciesId !== null && $newSpeciesId !== $planting->plant_species_id;
        $effectiveSpeciesId = $speciesChanged ? $newSpeciesId : $planting->plant_species_id;

        if ($speciesChanged) {
            $this->validateSpecies($owner, $newSpeciesId);
        }

        if ($newVarietyId !== false) {
            $varietyId = $newVarietyId;
            $varietyChanged = $varietyId !== $planting->plant_variety_id;

            if ($varietyChanged || $speciesChanged) {
                if ($varietyId !== null) {
                    $this->validateVariety($owner, $effectiveSpeciesId, $varietyId);
                }
            }
        }

        if ($newAreaId !== false) {
            $areaId = $newAreaId;
            $areaChanged = $areaId !== $planting->area_id;

            if ($areaChanged && $areaId !== null) {
                $this->validateArea($garden, $areaId);
            }
        }

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

        $newStatus = isset($data['status']) ? PlantingStatus::from($data['status']) : $planting->status;
        $removedAt = $this->resolveRemovedAt($planting, $newStatus);

        $planting->update($data);

        if ($removedAt !== false) {
            $planting->forceFill(['removed_at' => $removedAt])->save();
        }

        return $planting->fresh();
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

    private function validateVariety(User $owner, int $speciesId, int $varietyId): void
    {
        $exists = PlantVariety::selectableForPlanting($owner)
            ->where('id', $varietyId)
            ->where('plant_species_id', $speciesId)
            ->exists();

        if (! $exists) {
            throw new CatalogActionException('Die gewählte Sorte ist nicht verfügbar oder gehört nicht zur gewählten Pflanze.');
        }
    }

    private function validateArea(Garden $garden, int $areaId): void
    {
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

    /**
     * Returns the new removed_at value, or false if no change is needed.
     *
     * @return CarbonImmutable|null|false
     */
    private function resolveRemovedAt(GardenPlanting $planting, PlantingStatus $newStatus): mixed
    {
        if ($newStatus === PlantingStatus::Removed) {
            if ($planting->removed_at === null) {
                return now();
            }

            return false;
        }

        return null;
    }
}
