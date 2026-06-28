import { locationSchema, type Location } from '@/types/location.ts'
import { z } from 'zod'

import axios from '@/lib/axios.ts'

const createLocation = async ({
    shortCode,
    description,
}: z.infer<typeof locationSchema>): Promise<Location> => {
    const { data: { data } } = await axios.post(`/api/admin/locations`, {
        short_code: shortCode,
        description,
    })
    return data as Location
}

export default createLocation
