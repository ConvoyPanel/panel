import { rawDataToAddressPool } from '@/api/admin/addressPools/getAddressPools'
import http from '@/api/http'

interface UpdateAddressPoolParameters {
    name: string
}

const updateAddressPool = async (
    id: number,
    payload: UpdateAddressPoolParameters
) => {
    const {
        data: { data },
    } = await http.put(`/api/admin/address-pools/${id}`, payload)

    return rawDataToAddressPool(data)
}

export default updateAddressPool
