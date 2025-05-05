import { NetworkInterface } from '@/types/network-interface.ts'

import axios from '@/lib/axios.ts'

import { rawDataToNetworkInterface } from '@/api/transformers/network-interface.ts'

const getNetworkInterfaces = async (
    nodeId: number
): Promise<NetworkInterface[]> => {
    const {
        data: { data },
    } = await axios.get(`/api/admin/nodes/${nodeId}/network-interfaces`)

    return data.map(rawDataToNetworkInterface)
}

export default getNetworkInterfaces
