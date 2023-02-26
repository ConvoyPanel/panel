<?php

namespace Convoy\Rules;

use Illuminate\Contracts\Validation\Rule;

class Hostname implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return (bool) filter_var($value, FILTER_VALIDATE_DOMAIN);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ':attribute is invalid.';
    }
}
