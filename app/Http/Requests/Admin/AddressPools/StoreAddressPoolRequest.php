<?php

namespace Convoy\Http\Requests\Admin\AddressPools;

use Convoy\Models\AddressPool;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressPoolRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = AddressPool::getRules();

        return [
            ...$rules,
            'node_ids' => 'sometimes|array',
            'node_ids.*' => 'exists:nodes,id|integer',
        ];
    }
}
