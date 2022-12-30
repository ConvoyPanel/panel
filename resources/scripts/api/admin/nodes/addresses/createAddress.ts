import http from '@/api/http'
import { AddressType } from '@/api/server/getServer'
import { Address, rawDataToAddressObject } from '@/api/server/getServer'

export interface AddressParameters {
    serverId?: number
    address: string
    cidr: number
    gateway: string
    macAddress?: string
    type: AddressType
    syncNetworkConfig: boolean
}

const createAddress = async (nodeId: number, payload: AddressParameters): Promise<Address> => {
    const { data: { data } } = await http.post(`/api/admin/nodes/${nodeId}/addresses`, {
        server_id: payload.serverId,
        address: payload.address,
        cidr: payload.cidr,
        gateway: payload.gateway,
        mac_address: payload.macAddress,
        type: payload.type,
        sync_network_config: payload.syncNetworkConfig,
    })

    return rawDataToAddressObject(data)
}

export default createAddress