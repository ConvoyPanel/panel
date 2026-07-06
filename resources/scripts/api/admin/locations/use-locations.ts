import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'

import getAttachedNodes from '@/api/admin/locations/getAttachedNodes.ts'
import getLocation from '@/api/admin/locations/getLocation.ts'
import getLocations, {
    LocationQueryParams,
} from '@/api/admin/locations/getLocations.ts'

export const locationQueries = {
    all: () => ['admin', 'locations'] as const,
    lists: () => [...locationQueries.all(), 'list'] as const,
    list: (params: LocationQueryParams) =>
        queryOptions({
            queryKey: [...locationQueries.lists(), params] as const,
            queryFn: () => getLocations(params),
            placeholderData: keepPreviousData,
        }),
    details: () => [...locationQueries.all(), 'detail'] as const,
    detail: (id: number | null | undefined) =>
        queryOptions({
            queryKey: [...locationQueries.details(), id] as const,
            queryFn: () => getLocation(id!),
            enabled: id != null,
        }),
    nodes: (id: number | null | undefined) =>
        queryOptions({
            queryKey: [...locationQueries.detail(id).queryKey, 'nodes'] as const,
            queryFn: () => getAttachedNodes(id!),
            enabled: !!id,
        }),
}

const useLocations = (params: LocationQueryParams) =>
    useQuery(locationQueries.list(params))

export default useLocations
