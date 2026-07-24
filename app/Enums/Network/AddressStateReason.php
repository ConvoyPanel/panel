<?php

namespace App\Enums\Network;

/**
 * Why an address is in its current state. Deliberately kept off the allocator's path: allocation
 * only ever asks whether state is `available`, so a new reason can be added without touching the
 * allocation query or growing the set of states an operator has to understand.
 *
 * Null for addresses whose state needs no explanation (available, or assigned to a server).
 */
enum AddressStateReason: string
{
    /**
     * Reserved by the panel itself because handing it to a VM would break the subnet: the network
     * and broadcast addresses, the IPv6 subnet-router anycast, and the block's gateway. These
     * cannot be unreserved — see AddressBlock::systemReservedAddresses().
     */
    case System = 'system';

    /** Reserved by an operator to hold it out of the pool. Can be unreserved again. */
    case Admin = 'admin';
}
