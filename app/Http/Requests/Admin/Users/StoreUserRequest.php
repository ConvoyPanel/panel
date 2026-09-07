<?php

namespace App\Http\Requests\Admin\Users;

use App\Models\User;
use App\Rules\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
        $rules = User::getRules();

        return [
            'name' => $rules['name'],
            'email' => $rules['email'],
            // Optional now: omitting it invites the account instead, which is the flow that
            // exists so nobody has to pick — or email — a password on someone else's behalf.
            'password' => ['nullable', ...PasswordPolicy::rules()],
            'root_admin' => $rules['root_admin'],
        ];
    }
}
