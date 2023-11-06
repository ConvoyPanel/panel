import { AddressInclude } from '@/api/admin/nodes/addresses/getAddresses'
import http from '@/api/http'
import { AddressType, rawDataToAddress } from '@/api/server/getServer'

interface UpdateAddressParameters {
    address: string
    type: AddressType
    cidr: number
    gateway: string
    macAddress: string | null
    serverId: number | null
    include?: AddressInclude[]
}

const updateAddress = async (
    poolId: number,
    addressId: number,
    { macAddress, serverId, include, ...payload }: UpdateAddressParameters
) => {
    const {
        data: { data },
    } = await http.put(
        `/api/admin/address-pools/${poolId}/addresses/${addressId}`,
        {
            mac_address: macAddress,
            server_id: serverId,
            ...payload,
        },
        {
            params: {
                include: include?.join(','),
            },
        }
    )

    return rawDataToAddress(data)
}

export default updateAddress