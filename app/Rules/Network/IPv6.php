<?php

namespace App\Rules\Network;

use Illuminate\Contracts\Validation\Rule;

class IPv6 implements Rule
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
    public function passes($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ':attribute is not a valid IPv6 address.';
    }
}
