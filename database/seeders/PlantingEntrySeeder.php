<?php

namespace Database\Seeders;

use App\Enums\EntryType;
use App\Models\Garden;
use App\Models\GardenPlanting;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlantingEntrySeeder extends Seeder
{
    /** @var array<int, array{entry_type: EntryType, notes: string|null, days_ago: int}> */
    private array $entryTemplates = [
        ['entry_type' => EntryType::Observation, 'notes' => 'Erste Blätter sichtbar. Guter Start.', 'days_ago' => 30],
        ['entry_type' => EntryType::Care, 'notes' => 'Gedüngt und gegossen.', 'days_ago' => 15],
        ['entry_type' => EntryType::Observation, 'notes' => 'Wächst gut. Keine Auffälligkeiten.', 'days_ago' => 5],
    ];

    public function run(): void
    {
        $user = User::where('email', 'chrisganzert@gmail.com')->first();

        if (! $user) {
            return;
        }

        $garden = Garden::where('user_id', $user->id)->whereNull('deleted_at')->first();

        if (! $garden) {
            return;
        }

        $plantings = GardenPlanting::where('garden_id', $garden->id)
            ->whereNull('deleted_at')
            ->limit(3)
            ->get();

        foreach ($plantings as $planting) {
            if ($planting->entries()->count() > 0) {
                continue;
            }

            foreach ($this->entryTemplates as $template) {
                $planting->entries()->create([
                    'entry_type' => $template['entry_type'],
                    'notes' => $template['notes'],
                    'occurred_at' => now()->subDays($template['days_ago'])->toDateString(),
                ]);
            }
        }
    }
}
