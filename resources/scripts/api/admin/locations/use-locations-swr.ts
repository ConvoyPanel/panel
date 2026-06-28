import { PaginatedLocations } from '@/types/location.ts'
import useSWR from '@/lib/swr'

import getLocations from '@/api/admin/locations/getLocations.ts'

export const getKey = (query?: string, page?: number) => [
    'locations',
    query,
    page,
]

const useLocationsSWR = (query?: string, page?: number) => {
    return useSWR<PaginatedLocations>(getKey(query, page), () =>
        getLocations({
            page,
            filters: {
                '*': query,
            },
        })
    )
}

export default useLocationsSWR
