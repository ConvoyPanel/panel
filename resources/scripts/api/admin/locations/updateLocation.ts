import http from '@/api/http'
import { Location, rawDataToLocation } from '@/api/admin/locations/getLocations'

const updateLocation = async (id: number, shortCode: string, description: string | null): Promise<Location> => {
    const {
        data: { data },
    } = await http.put(`/api/admin/locations/${id}`, {
        short_code: shortCode,
        description,
    })

    return rawDataToLocation(data)
}

export default updateLocation
