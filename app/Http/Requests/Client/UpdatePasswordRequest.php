<?php

namespace App\Http\Requests\Client;

use App\Http\Requests\BaseApiRequest;
use App\Rules\PasswordPolicy;
use App\Services\Users\AccountPolicyResolver;

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

    /**
     * Overridden away from the base class's admin check — this is the account acting on itself —
     * and then narrowed by the panel-wide policy, which an operator turns off when sign-in
     * credentials are owned elsewhere (an OIDC directory, say) and a local password change would
     * change nothing that actually logs the person in.
     *
     * The forgot-password flow is held to the same answer; see
     * {@see AccountPolicyResolver::canResetPassword()}.
     */
    public function authorize(): bool
    {
        // Resolved rather than injected: the base class fixes this signature.
        return app(AccountPolicyResolver::class)->for($this->user())->canChangePassword;
    }
}
