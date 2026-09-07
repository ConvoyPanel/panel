<?php

namespace App\Data\Admin\Settings;

use App\Data\User\AccountCapabilitiesData;
use Spatie\LaravelData\Data;

/**
 * The panel-wide account policy, as edited on the admin Settings screen.
 *
 * Phrased as what is *allowed* rather than as the capabilities the client
 * screen reads ({@see AccountCapabilitiesData}): this is the stored policy, and
 * it is deliberately not the same object as the resolved answer for a given
 * user — an admin resolves past all four of these.
 */
class AccountSettingsData extends Data
{
    public function __construct(
        public bool $allowNameChange,
        public bool $allowEmailChange,
        public bool $allowPasswordChange,
        public bool $allowAvatarChange,
    ) {}
}
