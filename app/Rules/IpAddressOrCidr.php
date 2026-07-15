<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IpAddressOrCidr implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! $this->isValid($value)) {
            $fail('The :attribute field must be a valid IPv4 or IPv6 address or CIDR range.');
        }
    }

    private function isValid(string $value): bool
    {
        $parts = explode('/', trim($value));

        if (count($parts) === 1) {
            return filter_var($parts[0], FILTER_VALIDATE_IP) !== false;
        }

        if (count($parts) !== 2 || filter_var($parts[0], FILTER_VALIDATE_IP) === false) {
            return false;
        }

        [$address, $prefix] = $parts;

        if ($prefix === '' || ! ctype_digit($prefix)) {
            return false;
        }

        $maximum = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ? 32 : 128;

        return (int) $prefix <= $maximum;
    }
}
