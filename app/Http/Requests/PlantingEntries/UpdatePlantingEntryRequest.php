<?php

namespace App\Http\Requests\PlantingEntries;

use App\Enums\EntryType;
use App\Models\PlantingEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdatePlantingEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PlantingEntry $entry */
        $entry = $this->route('entry');

        return $this->user()->can('update', $entry);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('notes') && $this->notes === '') {
            $this->merge(['notes' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var PlantingEntry $entry */
        $entry = $this->route('entry');

        return [
            'entry_type' => ['required', Rule::enum(EntryType::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => [File::image()->types(['jpeg', 'png', 'webp'])->max(3 * 1024)],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('planting_entry_images', 'id')
                    ->where('planting_entry_id', $entry->id),
            ],
        ];
    }
}
