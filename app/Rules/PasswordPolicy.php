<?php

namespace App\Rules;

use Closure;
use Illuminate\Validation\Rules\Password as PasswordRule;

use function is_string;
use function strlen;

/**
 * The panel's one password policy, shared by every place a password is set: the account screen,
 * and an admin creating or resetting somebody else's account.
 *
 * It lived only in Client\UpdatePasswordRequest, so the admin endpoints quietly ran on Laravel's
 * `Password::defaults()` (eight characters, no breach check) — a weaker bar for the accounts an
 * operator hands out than for the one a user picks. `resources/scripts/utils/password.ts` mirrors
 * these numbers to the browser, and can only stay honest if there is a single set of them.
 */
final class PasswordPolicy
{
    /**
     * Length only, no character composition: NIST SP 800-63B says verifiers SHOULD NOT impose
     * composition rules, since they push people toward `Password1!` while discouraging the
     * passphrases that are actually strong.
     */
    public const MIN_LENGTH = 12;

    /** bcrypt hashes at most 72 *bytes* and silently ignores the rest. */
    public const MAX_BYTES = 72;

    /**
     * The rules a password must satisfy, without saying whether one is required — that is the
     * caller's decision (`required` when creating, `nullable` when an update may leave it alone).
     *
     * @return array<int, mixed>
     */
    public static function rules(): array
    {
        return [
            'string',
            // `uncompromised()` checks HIBP's k-anonymity range API (only a SHA-1 prefix leaves
            // the server) and fails open when it is unreachable, so an air-gapped install stays
            // usable.
            PasswordRule::min(self::MIN_LENGTH)->uncompromised(),
            self::withinByteCeiling(),
        ];
    }

    /**
     * Reject an over-long passphrase rather than quietly truncating it: bcrypt would accept it
     * while only its leading 72 bytes ever authenticated. Measured in bytes, not characters,
     * because that is the limit bcrypt actually applies — `max:72` counts characters (mb_strlen)
     * and would let a 72-character multibyte passphrase through at ~144 bytes. The ceiling still
     * clears the 64 characters NIST asks verifiers to accept.
     */
    public static function withinByteCeiling(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (is_string($value) && strlen($value) > self::MAX_BYTES) {
                $fail(__('Passwords may be at most :bytes bytes long.', ['bytes' => self::MAX_BYTES]));
            }
        };
    }
}
