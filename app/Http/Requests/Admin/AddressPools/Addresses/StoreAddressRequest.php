<?php

namespace Convoy\Http\Requests\Admin\AddressPools\Addresses;

use Convoy\Models\Address;
use Illuminate\Support\Arr;
use Convoy\Models\AddressPool;
use Illuminate\Validation\Validator;
use Convoy\Http\Requests\FormRequest;
use Convoy\Validation\ValidateAddressType;
use Convoy\Validation\ValidateAddressUniqueness;

class StoreAddressRequest extends FormRequest
{
    public function rules(): array
    {
        return Arr::except(Address::getRules(), ['address_pool_id']);
    }

    public function withValidator(Validator $validator)
    {
        $pool = $this->parameter('address_pool', AddressPool::class);

        $validator->after([
           new ValidateAddressType,
           new ValidateAddressUniqueness($pool->id),
        ]);
    }
}
