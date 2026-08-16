<?php

namespace App\Data\Server\Proxmox\Firewall;

use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

/**
 * An alias or IP set that may be named in a rule's source or destination.
 *
 * Convoy does not create these -- the network sync owns the VM's ipsets and
 * clears them wholesale -- but it does surface whatever exists so datacenter
 * groups an operator defined are pickable instead of having to be typed from
 * memory.
 */
class FirewallRefData extends Data
{
    public function __construct(
        /** `alias` or `ipset`. */
        public string $type,

        public string $name,

        /** The token to put in a rule: bare for aliases, `+`-prefixed for ipsets. */
        public string $reference,

        /** `dc` for cluster-wide, `guest` for this server's own. */
        public ?string $scope,

        public ?string $comment,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            type: Arr::get($raw, 'type'),
            name: Arr::get($raw, 'name'),
            reference: Arr::get($raw, 'ref'),
            scope: Arr::get($raw, 'scope'),
            comment: Arr::get($raw, 'comment'),
        );
    }
}
