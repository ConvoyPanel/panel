<?php

namespace App\Data\Anchor;

use App\Models\Node;
use Spatie\LaravelData\Data;

/**
 * A node hanging off an anchor, as its detail screen needs it: enough to name
 * it, link to it, and say what goes dark with the anchor. Deliberately not
 * NodeData -- that carries the whole Proxmox connection and every counted
 * relation, none of which this list shows.
 */
class AnchorNodeData extends Data
{
    public function __construct(
        public int $id,
        public string $displayName,
        public string $fqdn,
        public int $serversCount,
    ) {}

    public static function fromModel(Node $node): self
    {
        return new self(
            id: $node->id,
            displayName: $node->display_name,
            fqdn: $node->fqdn,
            serversCount: (int) ($node->servers_count ?? 0),
        );
    }
}
