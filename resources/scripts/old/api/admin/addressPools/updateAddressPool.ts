import { rawDataToAddressPool } from '@/api/admin/addressPools/getAddressPools'
import http from '@/api/http'

interface UpdateAddressPoolParameters {
    name: string
    nodeIds?: number[] | null
}

const updateAddressPool = async (
    id: number,
    { name, nodeIds }: UpdateAddressPoolParameters
) => {
    const {
        data: { data },
    } = await http.put(`/api/admin/address-pools/${id}`, {
        name,
        node_ids: nodeIds,
    })

    return rawDataToAddressPool(data)
}

export default updateAddressPool
