<?php

namespace App\Http\Requests\Plants;

use App\Models\PlantSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectPlantSpeciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reject', $this->route('plantSpecies'));
    }

    protected function prepareForValidation(): void
    {
        $note = trim((string) $this->input('moderation_note', ''));
        $dup = $this->input('duplicate_of_id');
        $this->merge([
            'moderation_note' => $note !== '' ? $note : null,
            'duplicate_of_id' => $dup !== '' && $dup !== null ? $dup : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PlantSpecies $species */
        $species = $this->route('plantSpecies');

        return [
            'moderation_note' => 'nullable|string|max:1000|required_without:duplicate_of_id',
            'duplicate_of_id' => [
                'nullable',
                'integer',
                'required_without:moderation_note',
                Rule::exists('plant_species', 'id')
                    ->where('catalog_status', 'approved')
                    ->whereNull('archived_at')
                    ->whereNull('deleted_at'),
                Rule::notIn([$species->id]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'moderation_note.required_without' => 'Bitte gib eine Begründung an oder wähle einen ähnlichen Eintrag.',
            'duplicate_of_id.required_without' => 'Bitte gib eine Begründung an oder wähle einen ähnlichen Eintrag.',
            'duplicate_of_id.exists' => 'Der gewählte Eintrag ist nicht verfügbar.',
            'duplicate_of_id.not_in' => 'Ein Eintrag kann nicht als Duplikat von sich selbst markiert werden.',
        ];
    }
}
