<?php

namespace Database\Factories;

use App\Enums\CatalogOrigin;
use App\Enums\CatalogStatus;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlantVariety>
 */
class PlantVarietyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plant_species_id' => PlantSpecies::factory()->system(),
            'name' => fake()->unique()->word(),
            'breeder' => null,
            'year_introduced' => null,
            'description' => null,
        ];
    }

    public function system(): static
    {
        return $this->state([
            'origin' => CatalogOrigin::System->value,
            'catalog_status' => CatalogStatus::Approved->value,
            'created_by' => null,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state([
            'origin' => CatalogOrigin::User->value,
            'catalog_status' => CatalogStatus::Private->value,
            'created_by' => $user->id,
        ]);
    }

    public function approved(): static
    {
        return $this->state(['catalog_status' => CatalogStatus::Approved->value]);
    }

    public function pendingReview(): static
    {
        return $this->state(['catalog_status' => CatalogStatus::PendingReview->value]);
    }

    public function rejected(): static
    {
        return $this->state(['catalog_status' => CatalogStatus::Rejected->value]);
    }

    public function archived(): static
    {
        return $this->state([
            'archived_at' => now(),
        ]);
    }
}
