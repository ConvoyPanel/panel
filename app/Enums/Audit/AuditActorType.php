<?php

namespace App\Enums\Audit;

use App\Settings\AuditSettings;

/**
 * What kind of thing performed an audited action, as the frontend needs to distinguish it.
 *
 * Note that STAFF is a *presentation* state, not a stored one: the actor morph only knows about
 * users and the system actor. An admin is reported as STAFF to non-admin viewers when
 * {@see AuditSettings::$reveal_staff_identity} is off.
 */
enum AuditActorType: string
{
    /** A named person the viewer is allowed to see. */
    case USER = 'user';

    /** An admin whose identity is withheld from this viewer. */
    case STAFF = 'staff';

    /** The panel itself, acting through a panel-wide application token. */
    case SYSTEM = 'system';

    /** Nobody was authenticated — a failed login, or an unattributable background action. */
    case UNKNOWN = 'unknown';
}
