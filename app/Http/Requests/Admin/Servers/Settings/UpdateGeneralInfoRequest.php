<?php

namespace Convoy\Http\Requests\Admin\Servers\Settings;

use Convoy\Http\Requests\FormRequest;
use Convoy\Models\Server;
use Convoy\Rules\Hostname;

class UpdateGeneralInfoRequest extends FormRequest
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

        return $this->requiredToOptional([
            'name' => $rules['name'],
            'hostname' => [...$rules['hostname'], ...[new Hostname]],
            'node_id' => $rules['node_id'],
            'user_id' => $rules['user_id'],
            'vmid' => $rules['vmid'],
            'status' => $rules['status'],
        ]);
    }
}
