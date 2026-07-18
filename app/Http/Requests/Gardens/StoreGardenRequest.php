<?php

namespace App\Http\Requests\Gardens;

use App\Data\Countries;
use App\Enums\GardenVisibility;
use App\Models\Garden;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGardenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Garden::class);
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim((string) $this->name);
        }

        if ($this->has('country') && $this->country !== null && $this->country !== '') {
            $data['country'] = mb_strtoupper(trim((string) $this->country));
        } elseif ($this->has('country') && ($this->country === null || $this->country === '')) {
            $data['country'] = null;
        }

        if ($this->has('region') && ($this->region === null || trim((string) $this->region) === '')) {
            $data['region'] = null;
        }

        if ($this->has('description') && ($this->description === null || trim((string) $this->description) === '')) {
            $data['description'] = null;
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
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', Rule::enum(GardenVisibility::class)],
            'size_sqm' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99'],
            'country' => ['nullable', 'string', Rule::in(Countries::codes())],
            'region' => ['nullable', 'string', 'max:100'],
        ];
    }
}
