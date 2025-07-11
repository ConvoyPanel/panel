import { NetworkInterface } from '@/types/network-interface.ts'

export const rawDataToNetworkInterface = (raw: any): NetworkInterface => ({
    id: raw.id,
    nodeId: raw.node_id,
    name: raw.name,
    description: raw.description,
})