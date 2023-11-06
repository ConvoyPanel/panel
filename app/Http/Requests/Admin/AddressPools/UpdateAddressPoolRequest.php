<?php

namespace Convoy\Http\Requests\Admin\AddressPools;

use Convoy\Models\AddressPool;
use Convoy\Http\Requests\FormRequest;

class UpdateAddressPoolRequest extends FormRequest
{
    public function rules(): array
    {
        $addressPool = $this->parameter('address_pool', AddressPool::class);

        return AddressPool::getRulesForUpdate($addressPool);
    }
}
