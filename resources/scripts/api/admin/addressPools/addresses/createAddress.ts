import { AddressInclude } from '@/api/admin/nodes/addresses/getAddresses'
import { AddressType, rawDataToAddress } from '@/api/server/getServer'
import http from '@/api/http'

interface CreateAddressParameters {
    address: string
    type: AddressType
    cidr: number
    gateway: string
    macAddress: string | null
    serverId: number | null
    include?: AddressInclude[]
}

const createAddress = async (
    poolId: number,
    { macAddress, serverId, include, ...payload }: CreateAddressParameters
) => {
    const {
        data: { data },
    } = await http.post(
        `/api/admin/address-pools/${poolId}/addresses`,
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

export default createAddress
