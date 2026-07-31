<?php

namespace App\Http\Requests\Admin\Servers\Settings;

use App\Http\Requests\BaseApiRequest;
use App\Models\Address;
use App\Models\Node;
use App\Models\Server;
use App\Rules\NetworkInterfaceBelongsToNode;
use App\Rules\VlanIsDeclaredOnInterface;
use Illuminate\Validation\Validator;

class UpdateBuildRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $server = $this->parameter('server', Server::class);
        $rules = Server::getRulesForUpdate($server);

        return [
            'cpu' => $rules['cpu'],
            'memory' => $rules['memory'],
            'disk' => $rules['disk'],
            'network_interface_id' => [
                'nullable',
                'integer',
                'exists:network_interfaces,id',
                new NetworkInterfaceBelongsToNode($server->node_id),
            ],
            'vlan_tag' => $rules['vlan_tag'],
            'address_ids' => 'sometimes|nullable|array',
            'address_ids.*' => 'integer|exists:ip_addresses,id',
            // NOTE: a dead 'backup_limit' => $rules['backup_limit'] line was removed
            // here — that rule key never existed (the column is backup_count_limit),
            // so it emitted an undefined-key warning and mapped to a phantom column.
            'bandwidth_limit' => $rules['bandwidth_limit'],
            'bandwidth_usage' => $rules['bandwidth_usage'],
            'backup_count_limit' => $rules['backup_count_limit'],
            'backup_size_limit' => $rules['backup_size_limit'],
            // Persistent NIC speed cap (bytes/s, null = unlimited) and the
            // per-server overage-penalty override (null = inherit node/global).
            'speed_limit' => $rules['speed_limit'],
            'overage_penalty' => $rules['overage_penalty'],
            'overage_penalty.action' => $rules['overage_penalty.action'],
            'overage_penalty.rate' => $rules['overage_penalty.rate'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $server = $this->parameter('server', Server::class);

                if ($this->has('address_ids')) {
                    $addresses = Address::whereIn('id', $this->input('address_ids') ?? [])->get();

                    foreach ($addresses as $address) {
                        if ($address->server_id !== null && $address->server_id !== $server->id) {
                            $validator->errors()->add(
                                'address_ids',
                                'One or more of the selected addresses are already in use',
                            );
                            break;
                        }
                    }
                }

                // Checked here rather than as a rule on `vlan_tag`: the tag can
                // stay untouched while the interface moves under it, which
                // still has to be caught.
                $vlanTag = $this->has('vlan_tag') ? $this->input('vlan_tag') : $server->vlan_tag;
                if (filled($vlanTag)) {
                    $rule = new VlanIsDeclaredOnInterface(
                        $this->input('network_interface_id', $server->network_interface_id),
                    );

                    $rule->validate(
                        'vlan_tag',
                        $vlanTag,
                        fn (string $message) => $validator->errors()->add('vlan_tag', $message),
                    );
                }

                // check if the memory and disk isn't exceeding the node limits
                $node = Node::findOrFail($server->node_id)->load('servers');

                $nodeMemoryLimit = ($node->memory * (($node->memory_overallocate / 100) + 1)) - ($node->memory_allocated - $server->memory);
                $nodeDiskLimit = ($node->disk * (($node->disk_overallocate / 100) + 1)) - ($node->disk_allocated - $server->disk);

                $memory = intval($this->input('memory'));
                $disk = intval($this->input('disk'));
                if ($memory > $nodeMemoryLimit || $memory < 0) {
                    $validator->errors()->add('memory', 'The memory value exceeds the node\'s limit.');
                }

                if ($disk > $nodeDiskLimit || $disk < 0) {
                    $validator->errors()->add('disk', 'The disk value exceeds the node\'s limit.');
                }
            },
        ];
    }
}
