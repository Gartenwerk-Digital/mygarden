<?php

namespace Database\Factories;

use App\Models\Garden;
use App\Models\GardenImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GardenImage>
 */
class GardenImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'garden_id' => Garden::factory(),
            'path' => 'gardens/1/'.fake()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(50_000, 2_000_000),
            'sort_order' => 0,
            'is_cover' => null,
        ];
    }

    public function cover(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cover' => true,
        ]);
    }
}
