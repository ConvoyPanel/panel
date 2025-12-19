import { rawDataToNode } from '@/api/transformers/node.ts'
import { NetworkInterface } from '@/types/network-interface.ts'

export const rawDataToNetworkInterface = (raw: any): NetworkInterface => ({
    id: raw.id,
    nodeId: raw.node_id,
    name: raw.name,
    description: raw.description,
    node: raw.node ? rawDataToNode(raw.node.data) : undefined,
})