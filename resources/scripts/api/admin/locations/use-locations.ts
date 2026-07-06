import { PaginatedLocations } from '@/types/location.ts'
import { keepPreviousData, useQuery } from '@tanstack/react-query'

import getLocations, { LocationQueryParams } from '@/api/admin/locations/getLocations.ts'

export const getKey = (params: LocationQueryParams) => ['locations', params]

const useLocations = (params: LocationQueryParams) => {
    return useQuery<PaginatedLocations>({
        queryKey: getKey(params),
        queryFn: () => getLocations(params),
        placeholderData: keepPreviousData,
    })
}

export default useLocations
