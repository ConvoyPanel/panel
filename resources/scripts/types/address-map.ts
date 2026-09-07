import { AddressStateKind } from '@/features/ipam/components/AddressStateLabel.tsx'

/** A unit with no address row yet: the position is real, the record is not. */
export type AddressMapState = AddressStateKind | 'ungenerated'

/** One cell of the address map — what sits at this position in the block's run of units. */
export interface AddressMapUnit {
    /** Position in the block, counting from zero. */
    index: number
    state: AddressMapState
    /** Null while the unit has no address row. */
    ip: string | null
    addressId: number | null
    serverName: string | null
}

/**
 * Every unit of a block, in address order, so the space can be drawn rather than paged through.
 *
 * The two flags say why a block is not drawable, so the UI can state the reason instead of
 * rendering an empty grid the reader has to interpret.
 */
export interface AddressMap {
    /** Minted on demand; there is no bounded run of units to draw. */
    sparse: boolean
    /** Bounded, but past what a grid can usefully show. */
    tooLarge: boolean
    totalUnits: number | null
    units: AddressMapUnit[]
}
