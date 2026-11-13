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
            // `integer` first: Laravel then skips `exists` for a value that isn't one,
            // which Postgres would refuse to compare with the bigint id.
            'node_ids.*' => 'integer|exists:nodes,id',
        ];
    }
}
