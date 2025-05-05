import { z } from 'zod'

import axios from '@/lib/axios.ts'

import { rawDataToNetworkInterface } from '@/api/transformers/network-interface.ts'

export const networkInterfaceSchema = z.object({
    name: z.string().min(1).max(40),
    description: z.string().max(191),
    mtu: z.preprocess(val => {
        // If the input is an empty string, treat it as null
        if (val === '') return null
        // If it's explicitly null or undefined, pass it through for nullable handling
        if (val === null || typeof val === 'undefined') return val
        // Otherwise, pass the original value for coercion/validation
        return val
    }, z.coerce.number().min(1).max(65535).nullable()),
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
