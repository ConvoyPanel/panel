<?php

namespace Convoy\Rules;

use Illuminate\Contracts\Validation\Rule;

class EnglishKeyboardCharacters implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes(string $attribute, $value): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9!@#$%^&*()_+\-=[\]{}|;\':",.\/<>?\\ ]*$/', $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must contain characters from the English keyboard.';
    }
}
