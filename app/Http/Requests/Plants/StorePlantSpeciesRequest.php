<?php

namespace App\Http\Requests\Plants;

use App\Enums\MoistureLevel;
use App\Enums\PlantCategory;
use App\Enums\PlantLifecycle;
use App\Enums\SoilType;
use App\Enums\SunExposure;
use App\Models\PlantSpecies;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlantSpeciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PlantSpecies::class);
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('common_name')) {
            $data['common_name'] = trim((string) $this->common_name);
        }

        foreach (['botanical_name', 'alternate_name', 'hardiness_note', 'description'] as $field) {
            if ($this->has($field)) {
                $value = trim((string) ($this->$field ?? ''));
                $data[$field] = $value !== '' ? $value : null;
            }
        }

        foreach ([
            'sun_exposures' => SunExposure::cases(),
            'soil_types' => SoilType::cases(),
            'moisture_levels' => MoistureLevel::cases(),
        ] as $field => $enumCases) {
            if (! $this->has($field)) {
                continue;
            }

            $input = $this->$field;
            if (! is_array($input) || count($input) === 0) {
                $data[$field] = null;

                continue;
            }

            $caseOrder = array_map(fn ($c) => $c->value, $enumCases);
            $strings = array_filter($input, 'is_string');
            $unique = array_unique($strings);
            $valid = array_values(array_filter($caseOrder, fn ($v) => in_array($v, $unique, true)));
            $invalid = array_values(array_diff($unique, $caseOrder));
            $merged = array_merge($valid, $invalid);

            $data[$field] = count($merged) > 0 ? $merged : null;
        }

        if (! empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'botanical_name.unique' => 'Dieser botanische Name ist bereits im Katalog erfasst oder wird derzeit geprüft.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'common_name' => ['required', 'string', 'max:150'],
            'botanical_name' => ['nullable', 'string', 'max:200', Rule::unique('plant_species', 'botanical_name')],
            'alternate_name' => ['nullable', 'string', 'max:150'],
            'category' => ['required', Rule::enum(PlantCategory::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'lifecycle' => ['nullable', Rule::enum(PlantLifecycle::class)],
            'hardiness_note' => ['nullable', 'string', 'max:255'],
            'sun_exposures' => ['nullable', 'array'],
            'sun_exposures.*' => [Rule::enum(SunExposure::class)],
            'soil_types' => ['nullable', 'array'],
            'soil_types.*' => [Rule::enum(SoilType::class)],
            'moisture_levels' => ['nullable', 'array'],
            'moisture_levels.*' => [Rule::enum(MoistureLevel::class)],
            'height_min_cm' => ['nullable', 'integer', 'min:1', 'max:60000'],
            'height_max_cm' => ['nullable', 'integer', 'min:1', 'max:60000', 'gte:height_min_cm'],
        ];
    }
}
