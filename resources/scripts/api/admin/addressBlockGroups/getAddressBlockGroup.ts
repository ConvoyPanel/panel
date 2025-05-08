import axios from '@/lib/axios.ts'

import { rawDataToAddressBlockGroup } from '@/api/transformers/address-block-group.ts'

const getAddressBlockGroup = async (id: number) => {
    const {
        data: { data },
    } = await axios.get(`/api/admin/address-block-groups/${id}`)

    return rawDataToAddressBlockGroup(data)
}

export default getAddressBlockGroup
