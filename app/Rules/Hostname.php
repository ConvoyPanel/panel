<?php

namespace App\Rules;

use const FILTER_FLAG_HOSTNAME;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Hostname implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! (bool) filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            $fail(__('validation.hostname'));
        }
    }
}
