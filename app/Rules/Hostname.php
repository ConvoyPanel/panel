<?php

namespace Convoy\Rules;

use Illuminate\Contracts\Validation\Rule;

class Hostname implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  mixed  $value
     */
    public function passes(string $attribute, $value): bool
    {
        return (bool) filter_var($value, FILTER_VALIDATE_DOMAIN);
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('validation.hostname');
    }
}
