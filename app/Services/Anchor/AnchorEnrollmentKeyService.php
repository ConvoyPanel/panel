<?php

namespace App\Services\Anchor;

use App\Enums\Anchor\AnchorMode;
use App\Models\AnchorEnrollmentKey;
use App\Models\User;
use Illuminate\Support\Str;

class AnchorEnrollmentKeyService
{
    /**
     * Distinguishes a key from the targeted rotation token minted by
     * {@see AnchorEnrollmentService}, whose prefix is `anc_enroll_`.
     *
     * The enrollment endpoint has to tell the two apart to know whether it is
     * re-keying a known installation or admitting a new one, and the answer
     * must not depend on a database lookup succeeding: an unrecognised token
     * has to fail as *the same kind* of token it was presented as, or a typo in
     * a rotation token starts being evaluated as an attempt to enroll.
     */
    public const TOKEN_PREFIX = 'anc_key_';

    /**
     * Default lifetime when the caller does not say. Matches the targeted
     * enrollment token, and errs the way an unattended credential should.
     */
    public const DEFAULT_TTL_MINUTES = 15;

    /**
     * Mints a key and returns it with its plaintext token attached.
     *
     * The plaintext exists in exactly one place in this codebase -- the return
     * value of this method -- and is never persisted or logged.
     */
    public function issue(
        string $name,
        ?AnchorMode $mode = null,
        ?int $maxUses = 1,
        ?int $expiresInMinutes = self::DEFAULT_TTL_MINUTES,
        ?User $actor = null,
    ): IssuedEnrollmentKey {
        $token = self::TOKEN_PREFIX.Str::random(64);

        $key = AnchorEnrollmentKey::create([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'token_hash' => hash('sha256', $token),
            'mode' => $mode,
            'max_uses' => $maxUses,
            'uses' => 0,
            'expires_at' => $expiresInMinutes === null
                ? null
                : now()->addMinutes($expiresInMinutes),
            'created_by' => $actor?->id,
        ]);

        return new IssuedEnrollmentKey($key->loadMissing('createdBy'), $token);
    }
}
