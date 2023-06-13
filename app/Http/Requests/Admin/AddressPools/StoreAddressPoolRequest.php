<?php

namespace Convoy\Http\Requests\Admin\AddressPools;

use Convoy\Models\AddressPool;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressPoolRequest extends FormRequest
{
    public function rules(): array
    {
        return AddressPool::getRules();
    }
}
