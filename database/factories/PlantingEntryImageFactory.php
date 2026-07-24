<?php

namespace Database\Factories;

use App\Models\PlantingEntry;
use App\Models\PlantingEntryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlantingEntryImage>
 */
class PlantingEntryImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'planting_entry_id' => PlantingEntry::factory(),
            'path' => 'planting-entries/1/'.fake()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(50_000, 2_000_000),
            'sort_order' => 0,
        ];
    }
}
