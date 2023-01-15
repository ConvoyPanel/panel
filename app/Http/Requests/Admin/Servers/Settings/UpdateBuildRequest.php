<?php

namespace Convoy\Http\Requests\Admin\Servers\Settings;

use Convoy\Http\Requests\FormRequest;
use Convoy\Models\Server;

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
}