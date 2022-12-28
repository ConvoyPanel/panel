import http from '@/api/http'
import { Location } from '@/api/admin/locations/getLocations'

export default (id: number, shortCode: string, description?: string): Promise<Location> =>
    http.put(`/api/admin/locations/${id}`, {
        short_code: shortCode,
        description,
    })
