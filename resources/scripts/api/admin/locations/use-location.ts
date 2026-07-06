import { Location } from '@/types/location.ts'
import { useQuery } from '@tanstack/react-query'

import getLocation from '@/api/admin/locations/getLocation.ts'

export const getKey = (id: number | null | undefined) => ['location', id]

const useLocation = (id?: number | null) => {
    return useQuery<Location>({
        queryKey: getKey(id),
        queryFn: () => getLocation(id!),
        enabled: id != null,
    })
}

export default useLocation
