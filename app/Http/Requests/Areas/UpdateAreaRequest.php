<?php

namespace App\Http\Requests\Areas;

use App\Enums\AreaType;
use App\Enums\MoistureLevel;
use App\Enums\SoilType;
use App\Enums\SunExposure;
use App\Models\Area;
use App\Models\Garden;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('area'));
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim((string) $this->name);
        }

        if ($this->has('description') && ($this->description === null || trim((string) $this->description) === '')) {
            $data['description'] = null;
        }

        foreach (['size_sqm', 'sun_exposure', 'soil_type', 'moisture_level', 'parent_id'] as $field) {
            if ($this->has($field) && ($this->{$field} === null || (string) $this->{$field} === '')) {
                $data[$field] = null;
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
        $garden = $this->route('garden');
        assert($garden instanceof Garden);

        $area = $this->route('area');
        assert($area instanceof Area);

        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(AreaType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'size_sqm' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99'],
            'sun_exposure' => ['nullable', Rule::enum(SunExposure::class)],
            'soil_type' => ['nullable', Rule::enum(SoilType::class)],
            'moisture_level' => ['nullable', Rule::enum(MoistureLevel::class)],
            'parent_id' => [
                'nullable',
                Rule::exists('areas', 'id')->where(function ($query) use ($garden, $area): void {
                    $query->where('garden_id', $garden->id)
                        ->whereNull('parent_id')
                        ->whereNull('deleted_at')
                        ->whereNull('archived_at')
                        ->where('id', '!=', $area->id);
                }),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $area = $this->route('area');
            assert($area instanceof Area);

            $parentId = $this->input('parent_id');

            if ($parentId !== null && $parentId == $area->id) {
                $validator->errors()->add('parent_id', __('Ein Bereich kann nicht sein eigener übergeordneter Bereich sein.'));

                return;
            }

            if ($area->children()->exists() && $parentId !== null) {
                $validator->errors()->add('parent_id', __('Ein Bereich mit Unterbereichen kann nicht selbst ein Unterbereich werden.'));
            }
        });
    }
}
