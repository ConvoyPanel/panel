import axios from '@/lib/axios.ts'

import { rawDataToLocation } from '@/api/transformers/location.ts'

const getLocation = async (id: number) => {
    const {
        data: { data },
    } = await axios.get(`/api/admin/locations/${id}`)

    return rawDataToLocation(data)
}

export default getLocation
