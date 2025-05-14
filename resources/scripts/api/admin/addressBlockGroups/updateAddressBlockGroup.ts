import { z } from 'zod'

import axios from '@/lib/axios.ts'

import { addressBlockGroupSchema } from '@/api/admin/addressBlockGroups/createAddressBlockGroup.ts'
import { rawDataToAddressBlockGroup } from '@/api/transformers/address-block-group'

const updateAddressBlockGroup = async (
    id: number,
    params: z.infer<typeof addressBlockGroupSchema>
) => {
    const {
        data: { data },
    } = await axios.put(`/api/admin/address-block-groups/${id}`, params)

    return rawDataToAddressBlockGroup(data)
}

export default updateAddressBlockGroup