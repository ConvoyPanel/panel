<?php

namespace App\Http\Requests\Admin\AddressPools\Addresses;

use App\Enums\Network\AddressType;
use App\Http\Requests\BaseApiRequest;
use App\Models\Address;
use App\Models\AddressPool;
use App\Validation\ValidateAddressType;
use App\Validation\ValidateAddressUniqueness;
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
