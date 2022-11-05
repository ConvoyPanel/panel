<?php

namespace Convoy\Transformers\Client;

use Convoy\Enums\Network\AddressType;
use Convoy\Models\Server;
use League\Fractal\TransformerAbstract;

class ServerTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Server $server)
    {
        $ipv4 = [];
        $ipv6 = [];

        foreach ($server->addresses as $address) {
            if ($address->type === AddressType::IPV4->value) {
                $ipv4[] = [
                    'address' => $address->address,
                    'cidr' => $address->cidr,
                    'gateway' => $address->gateway,
                    'mac_address' => $address->mac_address,
                ];
            } elseif ($address->type === AddressType::IPV6->value) {
                $ipv6[] = [
                    'address' => $address->address,
                    'cidr' => $address->cidr,
                    'gateway' => $address->gateway,
                    'mac_address' => $address->mac_address,
                ];
            }
        }

        return [
            'id' => $server->uuid_short,
            'internal_id' => $server->id,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'description' => $server->description,
            'status' => $server->status,
            'node_id' => $server->node_id,
            'usages' => [
                'bandwidth_usage' => $server->bandwidth_usage,
            ],
            'limits' => [
                'cpu' => $server->cpu,
                'memory' => $server->memory,
                'disk' => $server->disk,
                'snapshots' => $server->snapshot_limit,
                'backups' => $server->backup_limit,
                'bandwidth' => $server->bandwidth_limit,
                'addresses' => [
                    'ipv4' => $ipv4,
                    'ipv6' => $ipv6
                ]
            ]
        ];
    }
}
