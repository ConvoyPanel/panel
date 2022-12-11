import http from '@/api/http'
import { rawDataToLocation, Location } from '@/api/admin/locations/getLocations'

export default async (id: number, shortCode: string, description?: string): Promise<Location> => {
    const {
        data: { data },
    } = await http.put(`/api/admin/locations/${id}`, {
        short_code: shortCode,
        description,
    })

    return rawDataToLocation(data)
}
