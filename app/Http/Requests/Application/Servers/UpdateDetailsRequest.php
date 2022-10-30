<?php

namespace Convoy\Http\Requests\Application\Servers;

use Convoy\Http\Requests\Application\ApplicationFormRequest;
use Convoy\Models\Server;

class UpdateDetailsRequest extends ApplicationFormRequest
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
            'limits' => 'array|required',
            'limits.cpu' => $rules['cpu'],
            'limits.memory' => $rules['memory'],
            'limits.disk' => $rules['disk'],
            'limits.snapshot_limit' => $rules['snapshot_limit'],
            'limits.backup_limit' => $rules['backup_limit'],
            'limits.bandwidth_limit' => $rules['bandwidth_limit'],
            'limits.addresses' => $rules['addresses'],
            'limits.addresses.*' => $rules['addresses.*'],
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
