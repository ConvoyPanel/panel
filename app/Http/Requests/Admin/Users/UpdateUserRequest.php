<?php

namespace Convoy\Http\Requests\Admin\Users;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\User;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends BaseApiRequest
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
