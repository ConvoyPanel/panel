import { Location } from '@/types/location.ts'
import useSWR from 'swr'

import getLocation from '@/api/admin/locations/getLocation.ts'

export const getKey = (id: number) => ['location', id]

const useLocationSWR = (id?: number | null) => {
    return useSWR<Location>(id ? getKey(id) : null, () => getLocation(id!))
}

export default useLocationSWR
