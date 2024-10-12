<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Password implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     */
    public function validate(string $attribute, $value, Closure $fail): void
    {
        if (!(bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/', $value)) {
            $fail(__('validation.password.default'));
        }
    }
}
