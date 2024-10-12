<?php

namespace App\Validation;

use Illuminate\Validation\Validator;
use App\Enums\Network\AddressType;

class ValidateAddressType
{
    public function __construct(private AddressType $addressType, private array $fields)
    {
    }

    public function __invoke(Validator $validator)
    {
        $data = $validator->validated();

        if ($this->addressType === AddressType::IPV4) {
            foreach ($this->fields as $field) {
                if (! filter_var($data[$field], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $validator->errors()->add('address', __('validation.ipv4', ['attribute' => $field]));
                }
            }
        } elseif ($this->addressType === AddressType::IPV6) {
            foreach ($this->fields as $field) {
                if (! filter_var($data[$field], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $validator->errors()->add('address', __('validation.ipv6', ['attribute' => $field]));
                }
            }
        }
    }
}
