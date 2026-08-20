<?php

namespace App\Services\Anchor;

use App\Models\AnchorEnrollmentKey;

/**
 * A freshly minted key and the one copy of its plaintext token.
 *
 * The pair exists as a type so the token cannot quietly become a property of
 * the model and start riding along into responses, logs, or serialized jobs.
 * It is deliberately not a Data object: it never leaves the server.
 */
class IssuedEnrollmentKey
{
    public function __construct(
        public readonly AnchorEnrollmentKey $key,
        public readonly string $token,
    ) {}
}
