<?php

namespace Convoy\Http\Requests\Admin\Servers;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Address;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Rules\Password;
use Convoy\Rules\USKeyboardCharacters;
use Illuminate\Validation\Validator;

/**
 * @property mixed $type
 */
class StoreServerRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = Server::getRules();

        return [
            'name' => $rules['name'],
            'user_id' => $rules['user_id'],
            'node_id' => $rules['node_id'],
            // TODO: validation should be added for manually setting the vmid
            'vmid' => 'present|nullable|numeric|min:100|max:999999999',
            'hostname' => $rules['hostname'],
            'limits' => 'required|array',
            'limits.cpu' => $rules['cpu'],
            'limits.memory' => $rules['memory'],
            'limits.disk' => $rules['disk'],
            'limits.snapshots' => $rules['snapshot_limit'],
            'limits.backups' => $rules['backup_limit'],
            'limits.bandwidth' => $rules['bandwidth_limit'],
            'limits.address_ids' => 'sometimes|nullable|array',
            'limits.address_ids.*' => 'integer|exists:ip_addresses,id',
            'account_password' => ['required_if:should_create_server,1', 'string', 'min:8', 'max:191', new Password(
            ), new USKeyboardCharacters()],
            'should_create_server' => 'present|boolean',
            'template_uuid' => 'required_if:create_server,1|string|exists:templates,uuid',
            'start_on_completion' => 'present|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $addressIds = $this->input('limits.address_ids');

            if (! is_null($addressIds)) {
                $addresses = Address::whereIn('id', $addressIds)->get();

                foreach ($addresses as $address) {
                    if ($address->server_id !== null) {
                        $validator->errors()->add(
                            'limits.address_ids',
                            'One or more of the selected addresses are already in use',
                        );
                        break;
                    }
                }
            }

            // check if the memory and disk isn't exceeding the node limits
            $node = Node::findOrFail($this->input('node_id'))->load('servers');

            $nodeMemoryLimit = ($node->memory * (($node->memory_overallocate / 100) + 1)) - $node->memory_allocated;
            $nodeDiskLimit = ($node->disk * (($node->disk_overallocate / 100) + 1)) - $node->disk_allocated;

            $memory = intval($this->input('limits.memory'));
            $disk = intval($this->input('limits.disk'));

            if ($memory > $nodeMemoryLimit || $memory < 0) {
                $validator->errors()->add(
                    'limits.memory', 'The memory value exceeds the node\'s limit.',
                );
            }

            if ($disk > $nodeDiskLimit || $disk < 0) {
                $validator->errors()->add(
                    'limits.disk', 'The disk value exceeds the node\'s limit.',
                );
            }
        });
    }
}
