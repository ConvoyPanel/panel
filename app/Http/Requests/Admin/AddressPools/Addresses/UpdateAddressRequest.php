<?php

namespace Convoy\Http\Requests\Admin\AddressPools\Addresses;

use Convoy\Models\Address;
use Illuminate\Support\Arr;
use Convoy\Models\AddressPool;
use Illuminate\Validation\Validator;
use Convoy\Http\Requests\FormRequest;
use Convoy\Enums\Network\AddressType;
use Convoy\Validation\ValidateAddressType;
use Convoy\Validation\ValidateAddressUniqueness;

class UpdateAddressRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = Address::getRulesForUpdate($this->parameter('address', Address::class));

        return Arr::except($rules, 'address_pool_id');
    }

    public function after(): array
    {
        $pool = $this->parameter('address_pool', AddressPool::class);
        $address = $this->parameter('address', Address::class);

        return [
            new ValidateAddressType($this->enum('type', AddressType::class), ['address']),
            new ValidateAddressUniqueness($pool->id, $address->address),
        ];
    }
}
