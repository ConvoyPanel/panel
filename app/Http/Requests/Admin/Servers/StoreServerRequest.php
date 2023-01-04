<?php

namespace Convoy\Http\Requests\Admin\Servers;

use Convoy\Http\Requests\FormRequest;
use Convoy\Models\IPAddress;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Illuminate\Validation\Validator;

/**
 * @property mixed $type
 */
class StoreServerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = Server::getRules();

        return [
            'name' => $rules['name'],
            'user_id' => $rules['user_id'],
            'node_id' => $rules['node_id'],
            'vmid' => 'present|nullable|numeric|min:100|max:999999999',
            'hostname' => $rules['hostname'],
            'limits' => 'required|array',
            'limits.cpu' => $rules['cpu'],
            'limits.memory' => $rules['memory'],
            'limits.disk' => $rules['disk'],
            'limits.snapshots' => $rules['snapshot_limit'],
            'limits.backups' => $rules['backup_limit'],
            'limits.bandwidth' => $rules['bandwidth_limit'],
            'limits.address_ids' => 'present|nullable|array',
            'limits.address_ids.*' => 'integer|exists:ip_addresses,id',
            'account_password' => ['sometimes', 'nullable', 'string', 'min:8', 'max:191', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/u'],
            'should_create_server' => 'present|boolean',
            'template_uuid' => 'required_if:create_server,1|string|exists:templates,uuid',
            'start_on_completion' => 'present|boolean',
        ];
    }

    // check that all of the address_ids server_id is null
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $addressIds = $this->input('limits.address_ids');

            $addresses = IPAddress::whereIn('id', $addressIds)->get();

            foreach ($addresses as $address) {
                if ($address->server_id !== null) {
                    $validator->errors()->add('limits.address_ids', 'One or more of the selected addresses are already in use');
                    break;
                }
            }

            // check if the memory and disk isn't exceeding the node limits
            $node = Node::findOrFail($this->input('node_id'))->load('servers');

            $nodeMemoryLimit = ($node->memory * (($node->memory_overallocate / 100) + 1)) - $node->memory_allocated;
            $nodeDiskLimit = ($node->disk * (($node->disk_overallocate / 100) + 1)) - $node->disk_allocated;

            $memory = intval($this->input('limits.memory'));
            $disk = intval($this->input('limits.disk'));

            if ($memory > $nodeMemoryLimit || $memory < 0) {
                $validator->errors()->add('limits.memory', 'The memory value exceeds the node\'s limit.');
            }

            if ($disk > $nodeDiskLimit || $disk < 0) {
                $validator->errors()->add('limits.disk', 'The disk value exceeds the node\'s limit.');
            }
        });
    }
}
