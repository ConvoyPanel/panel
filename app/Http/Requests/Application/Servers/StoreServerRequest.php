<?php

namespace Convoy\Http\Requests\Application\Servers;

use Convoy\Http\Requests\Application\ApplicationFormRequest;
use Convoy\Models\Server;

class StoreServerRequest extends ApplicationFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = Server::getRules();

        return [
            'type' => $this->convertRule('sometimes', $rules['type'], 'required'),
            'user_id' => $rules['user_id'],
            'node_id' => $rules['node_id'],
            'template_id' => $rules['template_id'],
            'name' => $rules['name'],
            'vmid' => $this->convertRule('required', $rules['vmid'], 'required_if:type,existing'),
            'limits.cpu' => $rules['cpu'],
            'limits.memory' => $rules['memory'],
            'limits.disk' => $rules['disk'],
            'limits.addresses' => $rules['addresses'],
            'limits.addresses.*' => $rules['addresses.*'],
            'limits.snapshot_limit' => $rules['snapshot_limit'],
            'limits.backup_limit' => $rules['backup_limit'],
            'limits.bandwidth_limit' => $rules['bandwidth_limit'],
            'config.template' => $rules['template'],
            'config.visible' => $rules['visible'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();

        return [
            'type' => array_get($data, 'type'),
            'user_id' => array_get($data, 'user_id'),
            'node_id' => array_get($data, 'node_id'),
            'template_id' => array_get($data, 'template_id'),
            'name' => array_get($data, 'name'),
            'vmid' => array_get($data, 'vmid'),
            'limits' => [
                'cpu' => array_get($data, 'limits.cpu'),
                'memory' => array_get($data, 'limits.memory'),
                'disk' => array_get($data, 'limits.disk'),
                'address_ids' => array_get($data, 'limits.addresses'),
                'snapshot_limit' => array_get($data, 'limits.snapshot_limit'),
                'backup_limit' => array_get($data, 'limits.backup_limit'),
                'bandwidth_limit' => array_get($data, 'limits.bandwidth_limit'),
            ],
            'config' => [
                'template' => array_get($data, 'config.template'),
                'visible' => array_get($data, 'config.visible'),
            ],
        ];
    }
}
