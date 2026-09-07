<?php

namespace App\Settings;

use App\Models\User;
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
 * Panel-wide rather than per-account, because there is nothing below the
 * install to scope to: Convoy has one role bit ({@see User::$root_admin})
 * and no groups. Reads still go through {@see AccountPolicyResolver},
 * which takes the user it resolves for, so the scope a finer-grained policy
 * would need is already in the signature and adding one later touches the
 * resolver rather than every call site.
 *
 * All four default to true: the self-service surface shipped before these
 * settings did, and an upgrade must not quietly take it away.
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

    public static function group(): string
    {
        return 'account';
    }
}
