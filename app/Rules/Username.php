<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Username implements ValidationRule
{
    private const RESERVED = [
        'admin', 'api', 'app', 'dashboard', 'dev', 'garden', 'gardens',
        'login', 'logout', 'me', 'profile', 'register', 'root', 'settings',
        'support', 'system', 'user', 'users', 'help', 'about', 'contact',
        'terms', 'privacy', 'null', 'undefined', 'www',
    ];

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[a-z0-9_]{3,30}$/', $value)) {
            $fail('Der Benutzername darf nur Kleinbuchstaben, Ziffern und Unterstriche enthalten und muss 3–30 Zeichen lang sein.');

            return;
        }

        if (str_starts_with($value, '_')) {
            $fail('Der Benutzername darf nicht mit einem Unterstrich beginnen.');

            return;
        }

        if (str_ends_with($value, '_')) {
            $fail('Der Benutzername darf nicht mit einem Unterstrich enden.');

            return;
        }

        if (str_contains($value, '__')) {
            $fail('Der Benutzername darf keine aufeinanderfolgenden Unterstriche enthalten.');

            return;
        }

        if (in_array($value, self::RESERVED, strict: true)) {
            $fail('Dieser Benutzername ist nicht verfügbar.');
        }
    }
}
