import http from '@/api/http'
import { rawDataToAddressPool } from '@/api/admin/addressPools/getAddressPools'

const getAddressPool = async (id: number) => {
    const {
        data: { data },
    } = await http.get(`/api/admin/address-pools/${id}`)

    return rawDataToAddressPool(data)
}

export default getAddressPool
