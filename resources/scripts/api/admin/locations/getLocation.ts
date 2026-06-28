import axios from '@/lib/axios.ts'
import type { Location } from '@/types/location.ts'

const getLocation = async (id: number): Promise<Location> => {
    const { data: { data } } = await axios.get(`/api/admin/locations/${id}`)
    return data as Location
}

export default getLocation
