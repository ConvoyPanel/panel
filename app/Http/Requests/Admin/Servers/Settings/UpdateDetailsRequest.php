<?php

namespace App\Http\Requests\Admin\Servers\Settings;

use App\Http\Requests\BaseApiRequest;
use App\Models\Server;

class UpdateDetailsRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
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
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();

        // Adjust the limits field to match what is expected by the model.
        if (! empty($data['limits'])) {
            foreach ($data['limits'] as $key => $value) {
                $data[$key] = $value;
            }

            unset($data['limits']);
        }

        return $data;
    }
}
