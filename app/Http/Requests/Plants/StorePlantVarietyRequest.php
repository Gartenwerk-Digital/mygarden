<?php

namespace App\Http\Requests\Plants;

use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlantVarietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PlantSpecies $species */
        $species = $this->route('plantSpecies');

        return $this->user()->can('create', [PlantVariety::class, $species]);
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $name = trim((string) $this->name);
            $name = trim($name, "'\u{2018}\u{2019}\u{201A}\u{201B}");
            $data['name'] = $name;
        }

        foreach (['breeder', 'description'] as $field) {
            if ($this->has($field)) {
                $value = trim((string) ($this->$field ?? ''));
                $data[$field] = $value !== '' ? $value : null;
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
        /** @var PlantSpecies $species */
        $species = $this->route('plantSpecies');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('plant_varieties', 'name')->where('plant_species_id', $species->id),
            ],
            'breeder' => ['nullable', 'string', 'max:150'],
            'year_introduced' => ['nullable', 'integer', 'min:1500', 'max:'.(int) date('Y')],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
