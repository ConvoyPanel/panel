<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an OpenSSH-format public key (`<algorithm> <base64> [comment]`). Beyond a prefix check,
 * it base64-decodes the blob and asserts the length-prefixed algorithm name embedded in it matches
 * the declared algorithm — the same integrity check OpenSSH itself performs — so a truncated or
 * hand-mangled key is rejected rather than pushed to a VM's cloud-init.
 */
class SshPublicKey implements ValidationRule
{
    private const ALGORITHMS = [
        'ssh-rsa',
        'ssh-ed25519',
        'ssh-dss',
        'ecdsa-sha2-nistp256',
        'ecdsa-sha2-nistp384',
        'ecdsa-sha2-nistp521',
        'sk-ssh-ed25519@openssh.com',
        'sk-ecdsa-sha2-nistp256@openssh.com',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $invalid = fn () => $fail('The :attribute is not a valid SSH public key.');

        if (! is_string($value)) {
            $invalid();

            return;
        }

        $parts = preg_split('/\s+/', trim($value)) ?: [];

        if (count($parts) < 2) {
            $invalid();

            return;
        }

        [$algorithm, $encoded] = $parts;

        if (! in_array($algorithm, self::ALGORITHMS, true)) {
            $invalid();

            return;
        }

        $decoded = base64_decode($encoded, true);

        if ($decoded === false || strlen($decoded) < 4) {
            $invalid();

            return;
        }

        // The blob begins with a 4-byte big-endian length followed by the algorithm name; it must
        // echo the declared algorithm.
        $length = unpack('N', substr($decoded, 0, 4))[1];

        if ($length <= 0 || strlen($decoded) < 4 + $length) {
            $invalid();

            return;
        }

        if (substr($decoded, 4, $length) !== $algorithm) {
            $invalid();
        }
    }
}
