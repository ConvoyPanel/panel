<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Whether the panel will create accounts for people the provider never provisioned.
 *
 * Convoy has no public signup: every account normally arrives from the operator's automation.
 * Sharing a server with somebody who is not a customer therefore needs an account of a kind that
 * did not exist before, and an operator who does not want those has to be able to say so once.
 *
 * Off means off in both directions -- no guest is created, and no existing guest can sign in. A
 * switch that only stopped new ones would leave an operator with no way to close the door on the
 * ones already through, which is the whole reason to reach for it.
 *
 * Sharing with an address that already has an account is unaffected: that is one customer lending
 * another a hand, and it creates nothing.
 */
class PermissionSettings extends Settings
{
    // Whether sharing a server with an unknown email address creates a guest account for it, and
    // whether existing guest accounts may sign in.
    public bool $allow_guest_accounts = false;

    public static function group(): string
    {
        return 'permissions';
    }
}
