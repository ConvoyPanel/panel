import { locationSchema } from '@/types/location.ts'
import { z } from 'zod'

import axios from '@/lib/axios'

import { rawDataToLocation } from '@/api/transformers/location.ts'

const updateLocation = async (
    location: number,
    { shortCode, description }: z.infer<typeof locationSchema>
) => {
    const {
        data: { data },
    } = await axios.put(`/api/admin/locations/${location}`, {
        short_code: shortCode,
        description,
    })

    return rawDataToLocation(data)
}

export default updateLocation
