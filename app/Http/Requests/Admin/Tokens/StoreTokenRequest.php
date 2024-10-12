<?php

namespace App\Http\Requests\Admin\Tokens;

use Illuminate\Foundation\Http\FormRequest;

class StoreTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|between:1,191',
        ];
    }
}
