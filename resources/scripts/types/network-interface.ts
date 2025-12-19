import { Node } from '@/types/node.ts'
import { PaginatedResult } from '@/utils/http.ts'

export interface NetworkInterface {
    id: number
    nodeId: number
    name: string
    description: string | null
    node?: Node
}

export type PaginatedNetworkInterfaces = PaginatedResult<NetworkInterface>