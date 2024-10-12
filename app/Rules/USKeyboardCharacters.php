<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class USKeyboardCharacters implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!(bool)preg_match('/^[\x20-\x7F]*$/', $value)) {
            $fail(__('validation.us_keyboard_characters'));
        }
    }
}
