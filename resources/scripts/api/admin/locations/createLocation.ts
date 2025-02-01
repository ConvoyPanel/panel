import { locationSchema } from '@/types/location.ts'
import { z } from 'zod'

import axios from '@/lib/axios.ts'

import { rawDataToLocation } from '@/api/transformers/location'

const createLocation = async ({
    shortCode,
    description,
}: z.infer<typeof locationSchema>) => {
    const {
        data: { data },
    } = await axios.post(`/api/admin/locations`, {
        short_code: shortCode,
        description,
    })

    return rawDataToLocation(data)
}

export default createLocation
