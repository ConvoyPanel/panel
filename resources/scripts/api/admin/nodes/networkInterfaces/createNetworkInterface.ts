import { z } from 'zod'

import axios from '@/lib/axios.ts'

import { rawDataToNetworkInterface } from '@/api/transformers/network-interface.ts'

export const networkInterfaceSchema = z.object({
    name: z.string().min(1).max(40),
    description: z.string().max(191),
})

const createNetworkInterface = async (
    nodeId: number,
    payload: z.infer<typeof networkInterfaceSchema>
) => {
    const {
        data: { data },
    } = await axios.post(
        `/api/admin/nodes/${nodeId}/network-interfaces`,
        payload
    )

    return rawDataToNetworkInterface(data)
}

export default createNetworkInterface
