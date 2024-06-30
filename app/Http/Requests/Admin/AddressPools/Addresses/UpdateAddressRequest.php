<?php

namespace Convoy\Http\Requests\Admin\AddressPools\Addresses;

use Convoy\Enums\Network\AddressType;
use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Address;
use Convoy\Models\AddressPool;
use Convoy\Validation\ValidateAddressType;
use Convoy\Validation\ValidateAddressUniqueness;
use Illuminate\Support\Arr;

class UpdateAddressRequest extends BaseApiRequest
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
