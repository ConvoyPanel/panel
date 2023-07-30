<?php

namespace Convoy\Validation;

use Illuminate\Validation\Validator;

class ValidateAddressType
{
    public function __invoke(Validator $validator)
    {
        $data = $validator->validated();

        if ($data['type'] === 'ipv4') {
            if (! filter_var($data['address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $validator->errors()->add('address', 'The address must be a valid IPv4 address.');
            }

            if (! filter_var($data['gateway'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $validator->errors()->add('gateway', 'The gateway must be a valid IPv4 address.');
            }
        } elseif ($data['type'] === 'ipv6') {
            if (! filter_var($data['address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $validator->errors()->add('address', 'The address must be a valid IPv6 address.');
            }

            if (! filter_var($data['gateway'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $validator->errors()->add('gateway', 'The gateway must be a valid IPv6 address.');
            }
        }
    }
}