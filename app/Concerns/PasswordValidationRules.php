<?php

declare(strict_types=1);

namespace App\Concerns;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    private const int BCRYPT_MAX_BYTES = 72;

    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Closure|Password|ValidationRule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['bail', 'required', 'string', $this->fitsBcrypt(...), Password::default(), 'confirmed'];
    }

    /**
     * Bcrypt only reads the first 72 bytes, so a longer password would be cut off silently.
     */
    private function fitsBcrypt(string $attribute, string $value, Closure $fail): void
    {
        if (strlen($value) > self::BCRYPT_MAX_BYTES) {
            $fail('Das Passwort ist zu lang.');
        }
    }

    /**
     * Get the validation rules used to validate the current password.
     *
     * @return array<int, Password|ValidationRule|array<mixed>|string>
     */
    protected function currentPasswordRules(): array
    {
        return ['bail', 'required', 'string', 'current_password'];
    }
}
