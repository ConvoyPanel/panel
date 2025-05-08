<?php

namespace App\Validation;

use App\Enums\Network\AddressVersion;
use Illuminate\Validation\Validator;

class ValidateAddressType
{
    public function __construct(private AddressVersion $addressType, private array $fields)
    {
    }

    public function __invoke(Validator $validator)
    {
        $data = $validator->validated();

        if ($this->addressType === AddressVersion::IPV4) {
            foreach ($this->fields as $field) {
                if (! filter_var($data[$field], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $validator->errors()->add('address', __('validation.ipv4', ['attribute' => $field]));
                }
            }
        } elseif ($this->addressType === AddressVersion::IPV6) {
            foreach ($this->fields as $field) {
                if (! filter_var($data[$field], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $validator->errors()->add('address', __('validation.ipv6', ['attribute' => $field]));
                }
            }
        }
    }
}
