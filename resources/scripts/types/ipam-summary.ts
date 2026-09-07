import { AddressCapacity } from '@/types/address-capacity.ts'

/** The block a pool is about to run out of, named so the tile is actionable. */
export interface NearlyFullBlock {
    id: number
    addressBlockGroupId: number
    /** The block's CIDR — what an operator recognises it by. */
    label: string
    percent: number
}

/**
 * Headline figures for the IPAM index, across every pool rather than the page on screen.
 * Summing the visible rows would be wrong the moment a second page exists.
 */
export interface IpamSummary {
    capacity: AddressCapacity
    poolsCount: number
    blocksCount: number
    nodesCount: number
    blocksNearlyFull: number
    fullestBlock: NearlyFullBlock | null
}
