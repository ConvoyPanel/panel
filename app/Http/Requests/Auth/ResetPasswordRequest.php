<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    /** Open: the token in the URL is what authorizes this, checked by the controller. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:191'],
            // The same bar as every other place a password is set.
            'password' => ['required', 'confirmed', ...PasswordPolicy::rules()],
        ];
    }
}
