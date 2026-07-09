import { NetworkInterface } from '@/types/network-interface.ts'

export const rawDataToNetworkInterface = (raw: any): NetworkInterface => ({
    id: raw.id,
    nodeId: raw.nodeId,
    name: raw.name,
    description: raw.description,
    isVlanAware: raw.isVlanAware,
    vlanTag: raw.vlanTag,
    node: raw.node ?? undefined,
})
