<?php

namespace App\Settings;

use App\Services\Users\AccountPolicyResolver;
use Spatie\LaravelSettings\Settings;

/**
 * Operator policy for what a non-admin may change about their own account.
 *
 * The switches exist for deployments where something else owns the identity: a
 * billing system that provisions the account, or an OIDC directory it is
 * federated with. In those the panel is a mirror, and a user who edits their
 * own email here silently desyncs from the source of truth — which is what the
 * operator is turning off, not the ability to edit per se.
 *
 * Panel-wide rather than per-account. Admin roles scope what an operator may
 * reach in the admin area; they say nothing about what a customer may change
 * about their own account, and there is no grouping below the install for that
 * to hang off. Reads still go through {@see AccountPolicyResolver}, which takes
 * the user it resolves for, so the scope a finer-grained policy would need is
 * already in the signature and adding one later touches the resolver rather
 * than every call site.
 *
 * The four `allow_*_change` switches default to true: the self-service surface
 * shipped before these settings did, and an upgrade must not quietly take it
 * away.
 *
 * The two email switches sit here because they are about the same accounts:
 * whether someone can recover one by email, and whether its owner hears about
 * a new server. Password reset is off until an operator turns it on, since it
 * makes the mailbox a way into the account.
 */
class AccountSettings extends Settings
{
    /** Whether a non-admin may change their own display name. */
    public bool $allow_name_change = true;

    /** Whether a non-admin may change their own email address. */
    public bool $allow_email_change = true;

    /** Whether a non-admin may change their own password. */
    public bool $allow_password_change = true;

    /** Whether a non-admin may upload or remove their own profile picture. */
    public bool $allow_avatar_change = true;

    /**
     * Whether the sign-in page offers a reset link by email.
     *
     * Bounded by {@see $allow_password_change} for non-admins: an account that may not change
     * its password may not reset it either, or the reset would be the way around the policy.
     */
    public bool $allow_password_reset = false;

    /** Whether a server's owner gets an email when its first install finishes. */
    public bool $send_server_ready_email = true;

    public static function group(): string
    {
        return 'account';
    }
}
