import { AddressBlock } from '@/types/address-block.ts'
import { Server } from '@/types/server.ts'
import { PaginatedResult } from '@/utils/http.ts'

export enum AddressVersion {
    IPv4 = 'ipv4',
    IPv6 = 'ipv6',
}

export enum AddressState {
    Available = 'available',
    Assigned = 'assigned',
    Reserved = 'reserved',
}

export enum AddressStateReason {
    /** Reserved by the panel (network, broadcast, gateway) — cannot be unreserved. */
    System = 'system',
    /** Reserved by an operator to hold it out of the pool. */
    Admin = 'admin',
}

export interface Address {
    id: number
    addressBlockId: number
    serverId: number | null
    state: AddressState
    stateReason: AddressStateReason | null
    version: AddressVersion
    ip: string
    prefixLength: number
    /** Both come from the address block, so every address in a block repeats them. */
    gateway: string | null
    macAddress: string | null
    server: Server | null | undefined
    addressBlock?: AddressBlock
}

export type PaginatedAddresses = PaginatedResult<Address>

export interface GeneratedAddressesResult {
    createdCount: number
    /** Units still without a row. Generation writes in batches, so one run rarely finishes a block. */
    remaining: number
    isComplete: boolean
    /**
     * The block is minted on demand and has nothing to generate. Not an error and not a no-op the
     * user should read as failure — it is the correct answer for a sparse block.
     */
    sparse: boolean
}
