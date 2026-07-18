<?php

namespace Database\Factories;

use App\Enums\AreaType;
use App\Models\Area;
use App\Models\Garden;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'garden_id' => Garden::factory(),
            'parent_id' => null,
            'name' => fake()->words(fake()->numberBetween(1, 3), true),
            'type' => fake()->randomElement(AreaType::cases()),
            'description' => fake()->optional()->sentence(),
            'size_sqm' => null,
            'sun_exposure' => null,
            'soil_type' => null,
            'moisture_level' => null,
            'archived_at' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }

    public function withParent(Area $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'garden_id' => $parent->garden_id,
            'parent_id' => $parent->id,
        ]);
    }
}
