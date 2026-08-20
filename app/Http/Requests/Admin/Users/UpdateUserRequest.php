<?php

namespace App\Http\Requests\Admin\Users;

use App\Http\Requests\BaseApiRequest;
use App\Models\User;
use App\Rules\PasswordPolicy;

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
     * An untouched password field posts as an empty string, which is the same intent as omitting
     * it: leave the password alone. Normalising here keeps that from being reported as a policy
     * failure on a field the admin never filled in.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('password') === '') {
            $this->merge(['password' => null]);
        }
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
            'password' => ['nullable', ...PasswordPolicy::rules()],
            'root_admin' => $rules['root_admin'],
        ];
    }
}
