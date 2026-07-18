<?php

namespace Database\Factories;

use App\Enums\GardenVisibility;
use App\Models\Garden;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Garden>
 */
class GardenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(fake()->numberBetween(1, 3), true),
            'description' => fake()->optional()->sentence(),
            'visibility' => GardenVisibility::Private,
            'size_sqm' => null,
            'country' => null,
            'region' => null,
            'archived_at' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => GardenVisibility::Public,
        ]);
    }
}
