import { networkInterfaceSchema } from '@/api/admin/nodes/networkInterfaces/createNetworkInterface.ts'
import axios from '@/lib/axios.ts'
import { rawDataToNetworkInterface } from '@/api/transformers/network-interface.ts'
import { z } from 'zod'

const updateNetworkInterface = async (nodeId: number, interfaceId: number, payload: z.infer<typeof networkInterfaceSchema>)=> {
    const { data: { data } } = await axios.put(`/api/admin/nodes/${nodeId}/network-interfaces/${interfaceId}`, payload)

    return rawDataToNetworkInterface(data)
}

export default updateNetworkInterface