<?php

namespace Convoy\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EnglishKeyboardCharacters implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  mixed  $value
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!(bool) preg_match('/^[A-Za-z0-9!@#$%^&*()_+\-=[\]{}|;\':",.\/<>?\\ ]*$/', $value)) {
            $fail(__('validation.english_keyboard_characters'));
        }
    }
}
