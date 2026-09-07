<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

class AcceptInviteRequest extends FormRequest
{
    /**
     * Open by design: the token in the URL is the credential, and the person using it does not
     * have an account session yet — that is the whole point of the flow.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // The same bar as every other place a password is set, so an invited account is not
            // held to a weaker standard than one whose owner changes it later.
            'password' => ['required', 'confirmed', ...PasswordPolicy::rules()],
        ];
    }
}
