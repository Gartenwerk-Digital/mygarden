<?php

namespace App\Http\Requests\PlantingEntries;

use App\Enums\EntryType;
use App\Models\GardenPlanting;
use App\Models\PlantingEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StorePlantingEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var GardenPlanting $planting */
        $planting = $this->route('planting');

        return $this->user()->can('create', [PlantingEntry::class, $planting]);
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
        return [
            'entry_type' => ['required', Rule::enum(EntryType::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => [File::image()->types(['jpeg', 'png', 'webp'])->max(3 * 1024)],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $hasNotes = ! empty(trim((string) ($this->input('notes') ?? '')));
                $hasImages = $this->hasFile('images');

                if (! $hasNotes && ! $hasImages) {
                    $validator->errors()->add(
                        'notes',
                        __('Ein Eintrag muss mindestens eine Notiz oder ein Bild enthalten.')
                    );
                }
            },
        ];
    }
}
