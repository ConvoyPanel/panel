<?php

namespace Convoy\Http\Requests\Admin\AddressPools;

use Convoy\Models\AddressPool;
use Convoy\Http\Requests\FormRequest;

class UpdateAddressPoolRequest extends FormRequest
{
    public function rules(): array
    {
        $addressPool = $this->parameter('address_pool', AddressPool::class);

        return [
            ...AddressPool::getRulesForUpdate($addressPool),
            'node_ids' => 'sometimes|array',
            'node_ids.*' => 'exists:nodes,id|integer',
        ];
    }

    public function after(): array
    {
        // TODO: if nodes are removed, check whether their servers are using any of IPs from this pool before unmounting
        return [];
    }
}
