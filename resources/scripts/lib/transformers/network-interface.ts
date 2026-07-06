import { NetworkInterface } from '@/types/network-interface.ts'

export const rawDataToNetworkInterface = (raw: any): NetworkInterface => ({
    id: raw.id,
    nodeId: raw.nodeId,
    name: raw.name,
    description: raw.description,
    node: raw.node ?? undefined,
})
