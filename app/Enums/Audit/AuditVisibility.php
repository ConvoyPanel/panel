<?php

namespace App\Enums\Audit;

use App\Settings\AuditSettings;

/**
 * Whether an event may be shown to a non-admin who can otherwise see the subject.
 *
 * This governs the *event*, not the actor's identity — whether a client learns which staff member
 * acted is a separate operator policy, {@see AuditSettings::$reveal_staff_identity}.
 */
enum AuditVisibility: string
{
    /** Shown to anyone authorised to see the subject, including the owning client. */
    case CLIENT = 'client';

    /** Recorded, but only ever returned to admins. */
    case ADMIN_ONLY = 'admin_only';
}
