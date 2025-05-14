import { z } from 'zod'

import axios from '@/lib/axios.ts'

import { rawDataToAddressBlockGroup } from '@/api/transformers/address-block-group.ts'

export const addressBlockGroupSchema = z.object({
    name: z.string().min(1).max(40),
    description: z.string().max(191),
})

const createAddressBlockGroup = async (
    payload: z.infer<typeof addressBlockGroupSchema>
) => {
    const {
        data: { data },
    } = await axios.post('/api/admin/address-block-groups', payload)

    return rawDataToAddressBlockGroup(data)
}

export default createAddressBlockGroup