<?php

namespace Convoy\Http\Requests\Admin\AddressPools;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\AddressPool;
use Convoy\Models\Node;
use Illuminate\Validation\Validator;

class UpdateAddressPoolRequest extends BaseApiRequest
{
    public function rules(): array
    {
        /** @var AddressPool $addressPool */
        $addressPool = $this->parameter('address_pool', AddressPool::class);

        return [
            ...AddressPool::getRulesForUpdate($addressPool),
            'node_ids' => 'sometimes|array',
            'node_ids.*' => 'exists:nodes,id|integer',
        ];
    }

    public function after(): array
    {
        /** @var AddressPool $addressPool */
        $addressPool = $this->parameter('address_pool', AddressPool::class);

        return [
            function (Validator $validator) use ($addressPool) {
                /** @var int[] $nodeIdsToSync */
                if ($nodeIdsToSync = $this->node_ids) {
                    $existingAttachedNodeIds = $addressPool->nodes()->pluck('id');

                    $nodeIdsRemoved = $existingAttachedNodeIds->diff($nodeIdsToSync);

                    $isAddressesAllocated = Node::whereIn('nodes.id', $nodeIdsRemoved)->join(
                        'servers',
                        'nodes.id',
                        '=',
                        'servers.node_id',
                    )
                                                ->join(
                                                    'ip_addresses', 'servers.id', '=',
                                                    'ip_addresses.server_id',
                                                )
                                                ->where(
                                                    'ip_addresses.address_pool_id',
                                                    '=',
                                                    $addressPool->id,
                                                )
                                                ->exists();

                    if ($isAddressesAllocated) {
                        $validator->errors()->add(
                            'node_ids',
                            'Cannot detach nodes with servers using addresses from this pool.',
                        );
                    }
                }
            },
        ];
    }
}
