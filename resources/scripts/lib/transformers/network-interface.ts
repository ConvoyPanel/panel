import { NetworkInterface, Vlan } from '@/types/network-interface.ts'

export const rawDataToVlan = (raw: any): Vlan => ({
    id: raw.id ?? null,
    networkInterfaceId: raw.networkInterfaceId,
    tag: raw.tag,
    name: raw.name,
    description: raw.description,
    serversCount: raw.serversCount ?? 0,
})

export const rawDataToNetworkInterface = (raw: any): NetworkInterface => ({
    id: raw.id,
    nodeId: raw.nodeId,
    name: raw.name,
    description: raw.description,
    isVlanAware: raw.isVlanAware,
    vlanTag: raw.vlanTag,
    serversCount: raw.serversCount ?? 0,
    addressPoolsCount: raw.addressPoolsCount ?? 0,
    vlans: (raw.vlans ?? []).map(rawDataToVlan),
    node: raw.node ?? undefined,
})
