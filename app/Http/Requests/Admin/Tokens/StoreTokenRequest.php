<?php

namespace Convoy\Http\Requests\Admin\Tokens;

use Illuminate\Foundation\Http\FormRequest;

class StoreTokenRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|between:1,191',
        ];
    }
}
