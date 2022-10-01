<?php

namespace Convoy\Http\Requests\Admin\Servers\Settings;

use Convoy\Http\Requests\Admin\AdminFormRequest;
use Convoy\Models\Server;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBasicInfoRequest extends AdminFormRequest
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
    public function rules()
    {
        $rules = Server::getRulesForUpdate($this->parameter('server', Server::class));

        // TODO: finish all the basic infod
        return [
            'name' => $rules['name'],
            'node_id' => $rules['node_id'],
            'user_id' => $rules['user_id'],
            'vmid' => $rules['vmid'],
            'template' => $rules['template'],
            'visible' => $rules['visible'],
            'cpu' => $rules['cpu'],
            'memory' => $rules['memory'],
            'disk' => $rules['disk'],
            'snapshot_limit' => $rules['snapshot_limit'],
            'backup_limit' => $rules['backup_limit'],
            'bandwidth_limit' => $rules['bandwidth_limit'],
        ];
    }
}
