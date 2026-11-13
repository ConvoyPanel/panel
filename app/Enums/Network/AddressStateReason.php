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

    /**
     * Held for a server that is mid-migration: the destination binding exists
     * and is not active yet. Neutron's inactive port binding, in one column.
     *
     * The destination has to be reserved before the guest is committed to the
     * move, or a migration that succeeds can still land on an address someone
     * else took while the task ran. Turned into an assignment when the task
     * succeeds, back into `available` when it does not.
     */
    case Migration = 'migration';

    /**
     * An adopted guest was found using this address while the panel had it
     * assigned to a different server. Nothing is stolen and nothing is guessed:
     * the address is locked out of the pool until an operator resolves which
     * server really has it.
     */
    case Conflict = 'conflict';

    /**
     * Whether an operator may lift this hold from the address screen. System
     * reservations keep a subnet valid; a migration hold belongs to a running
     * task and unreserving it out from under one would double-allocate.
     */
    public function isOperatorReleasable(): bool
    {
        return $this === self::Admin || $this === self::Conflict;
    }
}
