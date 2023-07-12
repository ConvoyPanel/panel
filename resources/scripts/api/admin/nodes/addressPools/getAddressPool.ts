import http from '@/api/http'
import { rawDataToAddressPool } from '@/api/admin/nodes/addressPools/getAddressPools'

const getAddressPool = async (id: number) => {
    const { data } = await http.get(`/api/admin/address-pools/${id}`)

    return rawDataToAddressPool(data)
}

export default getAddressPool
