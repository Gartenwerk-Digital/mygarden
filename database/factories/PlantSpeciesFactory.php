<?php

namespace Database\Factories;

use App\Enums\CatalogOrigin;
use App\Enums\CatalogStatus;
use App\Enums\PlantCategory;
use App\Models\PlantSpecies;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlantSpecies>
 */
class PlantSpeciesFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'common_name' => fake()->words(2, true),
            'botanical_name' => null,
            'alternate_name' => null,
            'category' => fake()->randomElement(PlantCategory::cases())->value,
            'description' => null,
            'lifecycle' => null,
            'hardiness_note' => null,
            'sun_exposures' => null,
            'soil_types' => null,
            'moisture_levels' => null,
            'height_min_cm' => null,
            'height_max_cm' => null,
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
