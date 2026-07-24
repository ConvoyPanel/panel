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
    gateway: string
    macAddress: string | null
    server: Server | null | undefined
    addressBlock?: AddressBlock
}

export type PaginatedAddresses = PaginatedResult<Address>

export interface GeneratedAddressesResult {
    createdCount: number
    remaining: number
    isComplete: boolean
}
