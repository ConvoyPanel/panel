<?php

namespace App\Data\User;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * A freshly minted invite, returned exactly once.
 *
 * The link is here because mail cannot be relied on: plenty of self-hosted installs have no
 * working SMTP, and an invite that can only ever be emailed would make user creation impossible
 * on those. Handing the admin a copyable link means the flow works with no relay at all — and it
 * is still strictly better than emailing a password, because it expires and can be revoked.
 */
class UserInviteData extends Data
{
    public function __construct(
        public string $link,
        public CarbonImmutable $expiresAt,
        /** Whether the panel also emailed it, or the admin has to pass it on themselves. */
        public bool $emailed,
    ) {}
}
