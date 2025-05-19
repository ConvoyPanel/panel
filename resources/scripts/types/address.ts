import { Server } from '@/types/server.ts'
import { AddressBlock } from '@/types/address-block.ts'
import { PaginatedResult } from '@/utils/http.ts'

export enum AddressVersion {
    IPv4 = 'ipv4',
    IPv6 = 'ipv6',
}

export interface Address {
    id: number
    addressBlockId: number
    serverId: number | null
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