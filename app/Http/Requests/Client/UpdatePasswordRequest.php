<?php

namespace App\Http\Requests\Client;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => [
                'required',
                'string',
                'confirmed',
                // Length plus a breach check, and deliberately no character-composition rules:
                // NIST SP 800-63B says verifiers SHOULD NOT impose them, since they push people to
                // `Password1!` while discouraging passphrases. `uncompromised()` checks HIBP's
                // k-anonymity range API (only a SHA-1 prefix leaves the server) and fails open when
                // it is unreachable, so an air-gapped install stays usable.
                Password::min(12)->uncompromised(),
                // bcrypt hashes at most 72 *bytes* and silently ignores the rest, so a longer
                // passphrase would be accepted while only its leading 72 bytes ever authenticated.
                // Reject rather than quietly truncate. Measured in bytes, not characters, because
                // that is the limit bcrypt actually applies — `max:72` counts characters (mb_strlen)
                // and would let a 72-character multibyte passphrase through at ~144 bytes. The
                // ceiling still clears the 64 characters NIST asks verifiers to accept.
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (is_string($value) && strlen($value) > 72) {
                        $fail(__('Passwords may be at most 72 bytes long.'));
                    }
                },
            ],
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
