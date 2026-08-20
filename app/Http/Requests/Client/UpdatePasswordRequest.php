<?php

namespace App\Http\Requests\Client;

use App\Http\Requests\BaseApiRequest;
use App\Rules\PasswordPolicy;

class UpdatePasswordRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            // The policy itself — length, breach check, bcrypt's byte ceiling, and deliberately
            // no character-composition rules — lives in PasswordPolicy so the admin endpoints
            // that set somebody else's password hold to the same bar.
            'password' => ['required', 'confirmed', ...PasswordPolicy::rules()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
