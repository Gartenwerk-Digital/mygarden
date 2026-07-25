<?php

namespace App\Http\Requests\Gardens;

use App\Models\Garden;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreGardenImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Garden $garden */
        $garden = $this->route('garden');

        return $this->user()->can('update', $garden);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => [File::image()->types(['jpeg', 'png', 'webp'])->max(3 * 1024)],
        ];
    }
}
