<?php

namespace App\Enums\Network;

enum AddressState: string
{
    /** Free to be handed out by the allocator or assigned manually. */
    case Available = 'available';

    /** Currently attached to a server (mirrors server_id being set). */
    case Assigned = 'assigned';

    /**
     * Held out of the pool. Fully locked: the allocator skips it and it cannot be assigned until
     * it is unreserved. Network / broadcast / gateway addresses are auto-reserved this way.
     */
    case Reserved = 'reserved';
}
