<?php

namespace Convoy\Http\Requests\Admin\Servers;

use Convoy\Http\Requests\Admin\AdminFormRequest;
use Convoy\Models\Server;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $type
 */
class StoreServerRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = Server::getRules();
        $rules['type'] = $this->convertRule('sometimes', $rules['type'], 'required');
        $rules['vmid'] = $this->convertRule('required', $rules['vmid'], 'required_if:type,existing');

        return $rules;
    }
}
