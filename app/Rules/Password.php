<?php

namespace Convoy\Rules;

use Illuminate\Contracts\Validation\Rule;

class Password implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes(string $attribute, $value): bool
    {
        return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/', $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must contain 8 characters, 1 uppercase, 1 lowercase, 1 number and 1 special character.';
    }
}
