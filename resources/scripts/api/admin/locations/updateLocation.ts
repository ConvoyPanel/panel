import { locationSchema, type Location } from '@/types/location.ts'
import { z } from 'zod'

import axios from '@/lib/axios'

const updateLocation = async (
    location: number,
    { shortCode, description }: z.infer<typeof locationSchema>
): Promise<Location> => {
    const { data: { data } } = await axios.put(`/api/admin/locations/${location}`, {
        short_code: shortCode,
        description,
    })
    return data as Location
}

export default updateLocation
