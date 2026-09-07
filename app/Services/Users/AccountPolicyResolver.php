<?php

namespace App\Services\Users;

use App\Data\User\AccountCapabilitiesData;
use App\Models\User;
use App\Settings\AccountSettings;

/**
 * Resolves what an account may change about itself: the panel-wide
 * {@see AccountSettings}, minus the admin exemption.
 *
 * Single source of truth so the form requests that refuse a write and the
 * account screen that hides the field agree. Every read of the settings goes
 * through here — `for()` takes the user it is resolving for even though only
 * the admin bit is consulted today, so a per-user or per-group policy would
 * change this class alone.
 */
class AccountPolicyResolver
{
    public function __construct(private AccountSettings $settings) {}

    public function for(User $user): AccountCapabilitiesData
    {
        /*
         * An admin is never bound by these. They govern the self-service screen, and an admin who
         * hit a wall there would simply edit the same row from /admin/users — so enforcing it
         * against them restricts nothing and reads as a security boundary that isn't one.
         */
        if ($user->root_admin) {
            return AccountCapabilitiesData::unrestricted();
        }

        return $this->global();
    }

    /**
     * The panel-wide policy, before the admin exemption. This is the tier the
     * admin Settings screen edits.
     */
    public function global(): AccountCapabilitiesData
    {
        return new AccountCapabilitiesData(
            canChangeName: $this->settings->allow_name_change,
            canChangeEmail: $this->settings->allow_email_change,
            canChangePassword: $this->settings->allow_password_change,
            canChangeAvatar: $this->settings->allow_avatar_change,
        );
    }
}
