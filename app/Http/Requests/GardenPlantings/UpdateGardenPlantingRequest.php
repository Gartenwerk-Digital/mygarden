<?php

namespace App\Http\Requests\GardenPlantings;

use App\Enums\PlantingStatus;
use App\Enums\PlantingUnit;
use App\Models\Area;
use App\Models\Garden;
use App\Models\GardenPlanting;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGardenPlantingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var GardenPlanting $planting */
        $planting = $this->route('planting');

        return $this->user()->can('update', $planting);
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        foreach (['notes', 'nickname', 'planted_at', 'acquired_at', 'quantity', 'quantity_unit', 'plant_variety_id', 'area_id'] as $field) {
            if ($this->has($field)) {
                $value = $this->$field;
                $data[$field] = ($value === '' || $value === null) ? null : $value;
            }
        }

        if (! empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Garden $garden */
        $garden = $this->route('garden');

        /** @var GardenPlanting $planting */
        $planting = $this->route('planting');

        $user = $this->user();
        $unit = $this->input('quantity_unit');

        $quantityRules = ['nullable', 'required_with:quantity_unit'];
        if ($unit === PlantingUnit::Pieces->value) {
            $quantityRules[] = 'integer';
            $quantityRules[] = 'min:1';
            $quantityRules[] = 'max:99999999';
        } else {
            $quantityRules[] = 'numeric';
            $quantityRules[] = 'min:0.01';
            $quantityRules[] = 'max:99999999.99';
        }

        $newSpeciesId = $this->input('plant_species_id') !== null ? (int) $this->input('plant_species_id') : null;
        $speciesChanged = $newSpeciesId !== null && $newSpeciesId !== $planting->plant_species_id;
        $effectiveSpeciesId = $speciesChanged ? $newSpeciesId : $planting->plant_species_id;

        return [
            'plant_species_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('plant_species', 'id'),
                function (string $attribute, mixed $value, \Closure $fail) use ($user, $planting): void {
                    if ((int) $value === $planting->plant_species_id) {
                        return;
                    }

                    if (! PlantSpecies::selectableForPlanting($user)->where('id', $value)->exists()) {
                        $fail('Die gewählte Pflanze ist nicht verfügbar oder nicht auswählbar.');
                    }
                },
            ],
            'plant_variety_id' => [
                'sometimes',
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($user, $planting, $effectiveSpeciesId, $speciesChanged): void {
                    if ($value === null) {
                        return;
                    }

                    $varietyChanged = (int) $value !== $planting->plant_variety_id;

                    if (! $varietyChanged && ! $speciesChanged) {
                        return;
                    }

                    $exists = PlantVariety::selectableForPlanting($user)
                        ->where('id', $value)
                        ->where('plant_species_id', $effectiveSpeciesId)
                        ->exists();

                    if (! $exists) {
                        $fail('Die gewählte Sorte ist nicht verfügbar oder gehört nicht zur gewählten Pflanze.');
                    }
                },
            ],
            'area_id' => [
                'sometimes',
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($garden, $planting): void {
                    if ($value === null) {
                        return;
                    }

                    if ((int) $value === $planting->area_id) {
                        return;
                    }

                    $valid = Area::where('id', $value)
                        ->where('garden_id', $garden->id)
                        ->whereNull('archived_at')
                        ->whereNull('deleted_at')
                        ->exists();

                    if (! $valid) {
                        $fail('Der gewählte Bereich ist nicht gültig oder gehört nicht zu diesem Garten.');
                    }
                },
            ],
            'status' => ['required', Rule::enum(PlantingStatus::class)],
            'quantity' => $quantityRules,
            'quantity_unit' => ['nullable', Rule::enum(PlantingUnit::class), 'required_with:quantity'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'planted_at' => ['nullable', 'date'],
            'acquired_at' => ['nullable', 'date'],
        ];
    }
}
