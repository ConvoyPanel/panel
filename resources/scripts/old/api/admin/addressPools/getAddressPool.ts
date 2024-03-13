import { rawDataToAddressPool } from '@/api/admin/addressPools/getAddressPools'
import http from '@/api/http'

const getAddressPool = async (id: number) => {
    const {
        data: { data },
    } = await http.get(`/api/admin/address-pools/${id}`)

    return rawDataToAddressPool(data)
}

export default getAddressPool