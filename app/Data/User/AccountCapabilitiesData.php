<?php

namespace App\Data\User;

use App\Services\Users\AccountPolicyResolver;
use Spatie\LaravelData\Data;

/**
 * What the signed-in account is allowed to change about itself, as resolved by
 * {@see AccountPolicyResolver}.
 *
 * Rides along on `/api/client/user` so the account screen can hide a field it
 * would only be refused on. The server refuses it either way — this is what the
 * UI reads, not what enforces anything.
 */
class AccountCapabilitiesData extends Data
{
    public function __construct(
        public bool $canChangeName,
        public bool $canChangeEmail,
        public bool $canChangePassword,
        public bool $canChangeAvatar,
    ) {}

    /**
     * Unrestricted on every count — what an admin resolves to, and the shape a
     * fresh install starts in.
     */
    public static function unrestricted(): self
    {
        return new self(true, true, true, true);
    }
}
