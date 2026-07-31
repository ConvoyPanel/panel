import { Node } from '@/types/node.ts'
import { PaginatedResult } from '@/utils/http.ts'

export interface Vlan {
    /**
     * Null for a VLAN that is in use but never declared — a server carries the
     * tag, but nothing describes it. It can't be renamed or deleted until it
     * is declared.
     */
    id: number | null
    networkInterfaceId: number
    tag: number
    name: string | null
    description: string | null
    serversCount: number
}

export interface NetworkInterface {
    id: number
    nodeId: number
    name: string
    description: string | null
    isVlanAware: boolean
    vlanTag: number | null
    serversCount: number
    addressPoolsCount: number
    /** Declared and in-use VLANs merged, tag-ascending. Empty on a non-trunk. */
    vlans: Vlan[]
    node?: Node
}

export type PaginatedNetworkInterfaces = PaginatedResult<NetworkInterface>
