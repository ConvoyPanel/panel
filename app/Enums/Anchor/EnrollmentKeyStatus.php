<?php

namespace App\Enums\Anchor;

/**
 * Why a key will or will not admit the next machine that presents it.
 *
 * Derived, never stored. Two of these arrive by the clock alone, so a column
 * would be wrong the moment nobody wrote to it.
 */
enum EnrollmentKeyStatus: string
{
    case ACTIVE = 'active';

    /** Explicitly withdrawn by an admin. Terminal. */
    case REVOKED = 'revoked';

    /** Past `expires_at`. Terminal, and reached without anyone acting. */
    case EXPIRED = 'expired';

    /** Reached `max_uses`. Terminal for a single-use key the moment it works. */
    case EXHAUSTED = 'exhausted';
}
