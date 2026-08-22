<?php

namespace App\Support\Anchor;

use App\Models\Node;

/**
 * The panel's half of the Anchor protocol.
 *
 * A constant rather than a model member because three tables now carry an
 * installation, and the version the *panel* speaks is a property of the panel,
 * not of whichever row is being asked. It also keeps `STATUS_TTL_MINUTES` from
 * colliding with {@see Node::STATUS_TTL_MINUTES}, which answers a
 * different question -- whether the Proxmox API is responding, not whether the
 * agent is reporting in.
 */
final class AnchorProtocol
{
    public const VERSION = 1;

    /** How long a heartbeat stands for before an installation counts as gone. */
    public const STATUS_TTL_MINUTES = 5;
}
