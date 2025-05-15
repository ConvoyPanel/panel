import { AddressVersion } from '@/types/address.ts'
import { PaginatedResult } from '@/utils/http.ts'


export interface AddressBlock {
    id: number
    addressBlockGroupId: number
    name: string | null
    description: string | null
    version: AddressVersion
    baseIp: string
    gateway: string | null
    macAddress: string | null
    prefixLengthFrom: number
    prefixLengthTo: number
}

export type PaginatedAddressBlocks = PaginatedResult<AddressBlock>