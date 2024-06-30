<?php

namespace Convoy\Http\Requests\Admin\Servers\Settings;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Address;
use Convoy\Models\Node;
use Convoy\Models\Server;
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
            'address_ids' => 'present|nullable|array',
            'address_ids.*' => 'integer|exists:ip_addresses,id',
            'snapshot_limit' => $rules['snapshot_limit'],
            'backup_limit' => $rules['backup_limit'],
            'bandwidth_limit' => $rules['bandwidth_limit'],
            'bandwidth_usage' => $rules['bandwidth_usage'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $addressIds = $this->input('address_ids');

            $addresses = Address::whereIn('id', $addressIds)->get();

            $server = $this->parameter('server', Server::class);

            foreach ($addresses as $address) {
                if ($address->server_id !== null && $address->server_id !== $server->id) {
                    $validator->errors()->add(
                        'address_ids',
                        'One or more of the selected addresses are already in use',
                    );
                    break;
                }
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
        });
    }
}
