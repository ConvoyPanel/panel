<?php

namespace Convoy\Http\Requests\Admin\Users;

use Convoy\Http\Requests\FormRequest;
use Convoy\Models\User;
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
        $user = $this->parameter('user', User::class);

        $rules = User::getRulesForUpdate($user);

        return [
            'name' => $rules['name'],
            'email' => $rules['email'],
            'password' => [Password::defaults(), 'nullable'],
            'root_admin' => $rules['root_admin'],
        ];
    }
}
