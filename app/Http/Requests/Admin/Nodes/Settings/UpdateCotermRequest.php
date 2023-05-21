<?php

namespace Convoy\Http\Requests\Admin\Nodes\Settings;

use Convoy\Http\Requests\FormRequest;

class UpdateCotermRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'is_enabled' => 'required|boolean',
            'is_tls_enabled' => 'required|boolean',
            'fqdn' => 'required_if:is_enabled,1|string|max:191',
            'port' => 'required|integer',
        ];
    }

    public function validated($key = null, $default = null)
    {
        return [
            'coterm_enabled' => $this->input('is_enabled'),
            'coterm_tls_enabled' => $this->input('is_tls_enabled'),
            'coterm_fqdn' => $this->input('fqdn'),
            'coterm_port' => $this->input('port'),
        ];
    }
}
