<?php

namespace Database\Factories;

use App\Enums\PlantingStatus;
use App\Models\Garden;
use App\Models\GardenPlanting;
use App\Models\PlantSpecies;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GardenPlanting>
 */
class GardenPlantingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'garden_id' => Garden::factory(),
            'area_id' => null,
            'plant_species_id' => PlantSpecies::factory()->system(),
            'plant_variety_id' => null,
            'status' => PlantingStatus::Planned->value,
            'removed_at' => null,
            'quantity' => null,
            'quantity_unit' => null,
            'nickname' => null,
            'notes' => null,
            'planted_at' => null,
            'acquired_at' => null,
        ];
    }

    public function planted(): static
    {
        return $this->state(['status' => PlantingStatus::Planted->value]);
    }

    public function removed(): static
    {
        return $this->state([
            'status' => PlantingStatus::Removed->value,
            'removed_at' => now(),
        ]);
    }
}
