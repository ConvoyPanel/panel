<?php

namespace Convoy\Http\Requests\Admin\Users\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
        return [
            'name' => 'string|max:40|required',
            'email' => 'email|required',
            'password' => [Password::defaults(), 'nullable'],
            'root_admin' => 'boolean|required',
        ];
    }
}
