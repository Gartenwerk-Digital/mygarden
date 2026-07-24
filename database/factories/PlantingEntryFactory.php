<?php

namespace Database\Factories;

use App\Enums\EntryType;
use App\Models\GardenPlanting;
use App\Models\PlantingEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlantingEntry>
 */
class PlantingEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'garden_planting_id' => GardenPlanting::factory(),
            'entry_type' => EntryType::Observation->value,
            'notes' => fake()->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
        ];
    }

    public function withoutNotes(): static
    {
        return $this->state(['notes' => null]);
    }

    public function care(): static
    {
        return $this->state(['entry_type' => EntryType::Care->value]);
    }

    public function note(): static
    {
        return $this->state(['entry_type' => EntryType::Note->value]);
    }
}
