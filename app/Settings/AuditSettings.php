<?php

namespace App\Settings;

use App\Enums\Audit\AuditEvent;
use Spatie\LaravelSettings\Settings;

/**
 * Operator policy for what the audit log shows to non-admins.
 *
 * This is deliberately separate from the per-event
 * {@see AuditEvent::visibility()} flag. Whether an *event* is client-visible is a
 * property of the event and is decided once, in code. Whether a client learns *which* staff member
 * acted is a deployment-level judgement — an internal or single-tenant panel wants the name, a
 * public host does not — so it lives here, as one switch.
 */
class AuditSettings extends Settings
{
    /**
     * When false (the default), an admin actor is rendered to non-admin viewers as an anonymous
     * "Staff" rather than by name. The action itself is still shown; only the identity is masked.
     * Admin-facing views always show the real actor regardless of this setting.
     */
    public bool $reveal_staff_identity = false;

    public static function group(): string
    {
        return 'audit';
    }
}
