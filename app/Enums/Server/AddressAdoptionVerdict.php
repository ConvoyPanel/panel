<?php

namespace App\Enums\Server;

/**
 * What the panel would do with one address found on a guest it is adopting.
 *
 * Adoption is a reconciliation, not an import: the guest's `ipconfig0` is the
 * observation, the address rows are the model, and these are the ways the two
 * can disagree. See docs/ipam-migration-research.md §4 for the full table.
 *
 * Two rules hold across all of them. Nothing is ever written to the guest, and
 * an address the panel cannot resolve never stops the adoption: the server is
 * created with that address unclaimed and flagged, because refusing a whole
 * guest over one ambiguous IP is what makes operators stop using a feature.
 */
enum AddressAdoptionVerdict: string
{
    /** A free row for this exact address already exists. It gets assigned. */
    case Claim = 'claim';

    /**
     * The address falls inside a reachable block that never materialized this
     * row. It gets written, then assigned. The unique (block, ip) index is what
     * makes that safe.
     */
    case Mint = 'mint';

    /** Already assigned to this same server. A re-scan; nothing changes. */
    case AlreadyHeld = 'already_held';

    /**
     * Assigned to a different server. Never stolen: the guest is adopted
     * without it and both servers are named in the flag.
     */
    case Conflict = 'conflict';

    /** A network, broadcast or gateway address. A real problem on the node, surfaced rather than papered over. */
    case SystemReserved = 'system_reserved';

    /** Held out of the pool by an operator. Unreserve it and re-run to claim it. */
    case AdminReserved = 'admin_reserved';

    /** Inside a managed block that this node has no interface onto. The topology model is incomplete. */
    case Unreachable = 'unreachable';

    /** Inside more than one reachable block. Overlapping blocks are legal and nothing resolves the tie. */
    case Ambiguous = 'ambiguous';

    /** Outside every managed block. Recorded on the guest, owned by nobody. */
    case Unmanaged = 'unmanaged';

    /** Whether this verdict results in the server actually holding the address. */
    public function claimsTheAddress(): bool
    {
        return $this === self::Claim || $this === self::Mint || $this === self::AlreadyHeld;
    }
}
