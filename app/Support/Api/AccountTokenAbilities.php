<?php

namespace App\Support\Api;

/**
 * The scoped-ability vocabulary for end-user personal access tokens on the client API
 * (`/api/client`). An account token is bound to a single user and can only reach that user's own
 * resources — currently their servers. Account/security management (password, 2FA, passkeys,
 * minting more tokens) is session-only and never reachable by a token (see the `DenyApiTokenAccess`
 * guard on the `/account` group), so it is deliberately absent from this vocabulary.
 */
final class AccountTokenAbilities extends ScopedTokenAbilities
{
    /** Top-level client resources an account token may be scoped to. */
    public const RESOURCES = [
        'servers',
    ];

    protected const PATH_PREFIX = 'api/client/';
}
