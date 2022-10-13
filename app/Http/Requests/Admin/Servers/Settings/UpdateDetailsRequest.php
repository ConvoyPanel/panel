<?php

namespace Convoy\Http\Requests\Admin\Servers\Settings;

use Convoy\Http\Requests\Admin\AdminFormRequest;
use Convoy\Models\Server;

class UpdateDetailsRequest extends AdminFormRequest
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

        return [
            'limits.cpu' => $rules['cpu'],
            'limits.memory' => $rules['memory'],
            'limits.disk' => $rules['disk'],
            'limits.snapshot_limit' => $rules['snapshot_limit'],
            'limits.backup_limit' => $rules['backup_limit'],
            'limits.bandwidth_limit' => $rules['bandwidth_limit'],
        ];
    }



    /**
     * Convert the allocation field into the expected format for the service handler.
     *
     * @return array
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        // Adjust the limits field to match what is expected by the model.
        if (!empty($data['limits'])) {
            foreach ($data['limits'] as $key => $value) {
                $data[$key] = $value;
            }

            unset($data['limits']);
        }

        return $data;
    }
}
