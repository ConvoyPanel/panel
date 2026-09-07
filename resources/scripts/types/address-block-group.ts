import { AddressCapacity } from '@/types/address-capacity.ts'
import { PaginatedResult } from '@/utils/http.ts'

export interface AddressBlockGroup {
    id: number
    name: string
    description: string | null
    addressBlocksCount: number
    nodesCount: number
    capacity: AddressCapacity
}

export type PaginatedAddressBlockGroups = PaginatedResult<AddressBlockGroup>
