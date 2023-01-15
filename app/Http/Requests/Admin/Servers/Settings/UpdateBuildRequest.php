<?php

namespace Convoy\Http\Requests\Admin\Servers\Settings;

use Convoy\Http\Requests\FormRequest;
use Convoy\Models\IPAddress;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Illuminate\Validation\Validator;

class UpdateBuildRequest extends FormRequest
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
            'bandwidth_usage' => $rules['bandwidth_usage']
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $addressIds = $this->input('address_ids');

            $addresses = IPAddress::whereIn('id', $addressIds)->get();

            foreach ($addresses as $address) {
                if ($address->server_id !== null) {
                    $validator->errors()->add('address_ids', 'One or more of the selected addresses are already in use');
                    break;
                }
            }

            // check if the memory and disk isn't exceeding the node limits
            $node = Node::findOrFail($this->input('node_id'))->load('servers');

            $nodeMemoryLimit = ($node->memory * (($node->memory_overallocate / 100) + 1)) - $node->memory_allocated;
            $nodeDiskLimit = ($node->disk * (($node->disk_overallocate / 100) + 1)) - $node->disk_allocated;

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