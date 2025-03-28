import { PaginatedResult } from '@/utils/http.ts'

export interface Node {
    id: number
    locationId: number
    displayName: string
    name: string
    verifyTls: boolean
    fqdn: string
    port: number
    memory: number
    memoryOverallocate: number
    memoryAllocated: number
    cotermId: number | null
    serversCount: number
}

export type PaginatedNodes = PaginatedResult<Node>
