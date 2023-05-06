import http from '@/api/http'
import { rawDataToLocation, Location } from '@/api/admin/locations/getLocations'

export default async (shortCode: string, description: string | null): Promise<Location> => {
    const {
        data: { data },
    } = await http.post('/api/admin/locations', {
        short_code: shortCode,
        description,
    })

    return rawDataToLocation(data)
}
