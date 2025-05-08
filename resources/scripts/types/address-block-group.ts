import { PaginatedResult } from '@/utils/http.ts'

export interface AddressBlockGroup {
    id: number
    name: string
    description: string | null
    nodesCount: number
}

export type PaginatedAddressBlockGroups = PaginatedResult<AddressBlockGroup>