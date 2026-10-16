<?php

namespace App\Data\Server\Migration;

use Spatie\LaravelData\Data;

/**
 * What one specific destination does to this server's addresses, resolved down
 * to the actual IPs, so the operator reads them before committing rather than
 * discovering them afterwards. Linode's precedent: the destination addresses
 * are shown while the move is still a choice.
 *
 * The reallocated set is computed by running the real allocator inside a
 * transaction that is then rolled back, so it is what the allocator would hand
 * out and not a second implementation of the same rules. A concurrent
 * allocation can still take one of them between the preview and the commit, so
 * these are a faithful preview and not a reservation.
 */
class MigrationPreviewData extends Data
{
    public function __construct(
        public readonly MigrationCandidateData $candidate,
        /** @var array<int, MigrationAddressData> addresses that stay with the server */
        public readonly array $preserved,
        /** @var array<int, MigrationAddressData> addresses that go back to their pool */
        public readonly array $released,
        /** @var array<int, MigrationAddressData> addresses the server would get instead */
        public readonly array $allocated,
        /**
         * True when the destination cannot cover what the server holds: the
         * counts are short, or the destination has no pool of that version at
         * all. Migration is refused rather than moving a server to an address
         * count it did not ask for.
         */
        public readonly bool $isShortOnAddresses,
    ) {}
}
