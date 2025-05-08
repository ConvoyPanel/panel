<?php

namespace App\Http\Requests\Admin\AddressPools;

use App\Models\AddressBlockGroup;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressPoolRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = AddressBlockGroup::getRules();

        return [
            ...$rules,
            'node_ids' => 'sometimes|array',
            'node_ids.*' => 'exists:nodes,id|integer',
        ];
    }
}
