<?php

namespace Database\Seeders;

use App\Enums\PlantingStatus;
use App\Enums\PlantingUnit;
use App\Models\Area;
use App\Models\Garden;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\User;
use Illuminate\Database\Seeder;

class GardenPlantingSeeder extends Seeder
{
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

        $apple = PlantSpecies::where('common_name', 'Apfelbaum')->first();
        $lavender = PlantSpecies::where('common_name', 'Lavendel')->first();
        $tomato = PlantSpecies::where('common_name', 'Tomate')->first();
        $lawn = PlantSpecies::where('common_name', 'Rasen')->first();

        $elstar = $apple ? PlantVariety::where('plant_species_id', $apple->id)
            ->where('name', 'Elstar')
            ->first() : null;

        $hidcote = $lavender ? PlantVariety::where('plant_species_id', $lavender->id)
            ->where('name', 'Hidcote')
            ->first() : null;

        $orchardArea = Area::where('garden_id', $garden->id)
            ->where('name', 'Obstwiese')
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->first();

        $frontArea = Area::where('garden_id', $garden->id)
            ->where('name', 'Vorgarten')
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->first();

        $mainArea = Area::where('garden_id', $garden->id)
            ->where('name', 'Hauptfläche')
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->first();

        if ($apple) {
            $garden->plantings()->firstOrCreate(
                [
                    'plant_species_id' => $apple->id,
                    'plant_variety_id' => $elstar?->id,
                    'nickname' => 'Elstar am Zaun',
                ],
                [
                    'area_id' => $orchardArea?->id,
                    'status' => PlantingStatus::Planted->value,
                    'quantity' => '1.00',
                    'quantity_unit' => PlantingUnit::Pieces->value,
                    'planted_at' => '2024-03-15',
                ]
            );
        }

        if ($lavender) {
            $garden->plantings()->firstOrCreate(
                [
                    'plant_species_id' => $lavender->id,
                    'plant_variety_id' => $hidcote?->id,
                    'nickname' => null,
                ],
                [
                    'area_id' => $frontArea?->id,
                    'status' => PlantingStatus::Planted->value,
                    'quantity' => '3.00',
                    'quantity_unit' => PlantingUnit::Pieces->value,
                    'planted_at' => '2025-05-01',
                ]
            );
        }

        if ($tomato) {
            $garden->plantings()->firstOrCreate(
                [
                    'plant_species_id' => $tomato->id,
                    'plant_variety_id' => null,
                    'nickname' => null,
                    'area_id' => null,
                ],
                [
                    'status' => PlantingStatus::Planned->value,
                    'quantity' => null,
                    'quantity_unit' => null,
                    'notes' => 'Für nächste Saison geplant.',
                ]
            );
        }

        if ($lawn) {
            $garden->plantings()->firstOrCreate(
                [
                    'plant_species_id' => $lawn->id,
                    'plant_variety_id' => null,
                    'nickname' => null,
                ],
                [
                    'area_id' => $mainArea?->id,
                    'status' => PlantingStatus::Planted->value,
                    'quantity' => '12.50',
                    'quantity_unit' => PlantingUnit::SquareMeters->value,
                ]
            );
        }
    }
}
