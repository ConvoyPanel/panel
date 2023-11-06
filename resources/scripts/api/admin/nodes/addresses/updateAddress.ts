import { AddressParameters } from '@/api/admin/nodes/addresses/createAddress'
import http from '@/api/http'
import { Address, rawDataToAddress } from '@/api/server/getServer'

const updateAddress = async (
    nodeId: number,
    addressId: number,
    payload: AddressParameters
): Promise<Address> => {
    const {
        data: { data },
    } = await http.put(`/api/admin/nodes/${nodeId}/addresses/${addressId}`, {
        server_id: payload.serverId,
        address: payload.address,
        cidr: payload.cidr,
        gateway: payload.gateway,
        mac_address: payload.macAddress,
        type: payload.type,
    })

    return rawDataToAddress(data)
}

export default updateAddress