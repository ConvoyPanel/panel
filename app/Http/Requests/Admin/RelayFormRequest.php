<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseApiRequest;

class RelayFormRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'public_url' => ['required', 'url:http,https', 'max:2048'],
            'panel_url_override' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }
}
