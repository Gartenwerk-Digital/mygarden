<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Data\Countries;
use App\Enums\ProfileVisibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge(['username' => mb_strtolower(trim((string) $this->username))]);
        }

        if ($this->has('country') && $this->country !== null) {
            $this->merge(['country' => mb_strtoupper(trim((string) $this->country))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->nameDisplayRules(),
            'username' => $this->usernameRules($this->user()->getKey()),
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'country' => ['nullable', 'string', Rule::in(Countries::codes())],
            'region' => ['nullable', 'string', 'max:100'],
            'profile_visibility' => ['required', Rule::enum(ProfileVisibility::class)],
            'email' => $this->emailRules($this->user()->id),
        ];
    }
}
