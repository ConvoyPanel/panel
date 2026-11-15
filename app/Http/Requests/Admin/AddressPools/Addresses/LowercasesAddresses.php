<?php

namespace Convoy\Http\Requests\Admin\AddressPools\Addresses;

/**
 * IPv6 and MAC addresses are case-insensitive, so they're stored in lowercase.
 * Done before validation, so the uniqueness check compares like with like.
 */
trait LowercasesAddresses
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $fields = ['address', 'starting_address', 'ending_address', 'gateway', 'mac_address'];

        $this->merge(
            collect($this->only($fields))
                ->filter(fn ($value) => is_string($value))
                ->map(fn (string $value) => strtolower($value))
                ->all(),
        );
    }
}
