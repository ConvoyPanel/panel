import {
    keepPreviousData,
    queryOptions,
    useQuery,
} from '@tanstack/react-query'
import { z } from 'zod'

import { apiFetch, type DataResponse, type PaginatedResponse } from '@/lib/api'
import type { Location, PaginatedLocations, locationSchema } from '@/types/location.ts'
import type { Node } from '@/types/node.ts'
import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'
import LocationController from '@/wayfinder/actions/App/Http/Controllers/Admin/LocationController'

export type LocationQueryParams = QueryBuilderParams<'*' | 'short_code'>

// LocationController is exposed on both the panel (`/api/admin`) and the
// Application token API (`/api/application`), so Wayfinder emits URI-keyed
// dictionaries instead of callables — the panel references the admin route.
const indexRoute = LocationController.index['/api/admin/locations']
const showRoute = LocationController.show['/api/admin/locations/{location}']
const nodesRoute =
    LocationController.showAttachedNodes['/api/admin/locations/{location}/nodes']
const storeRoute = LocationController.store['/api/admin/locations']
const updateRoute = LocationController.update['/api/admin/locations/{location}']
const destroyRoute =
    LocationController.destroy['/api/admin/locations/{location}']

export const getLocations = async (
    params: LocationQueryParams
): Promise<PaginatedLocations> => {
    const res = await apiFetch<PaginatedResponse<Location>>(indexRoute(), {
        params: withQueryBuilderParams(params),
    })

    return { items: res.items, pagination: res.pagination }
}

const getLocation = async (id: number): Promise<Location> =>
    (await apiFetch<DataResponse<Location>>(showRoute(id))).data

const getAttachedNodes = async (id: number): Promise<Node[]> =>
    (await apiFetch<DataResponse<Node[]>>(nodesRoute(id))).data

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

export const useLocations = (params: LocationQueryParams) =>
    useQuery(locationQueries.list(params))

export const useLocation = (id?: number | null) =>
    useQuery(locationQueries.detail(id))

export const useAttachedNodes = (location: number | null | undefined) =>
    useQuery(locationQueries.nodes(location))

export const createLocation = async ({
    shortCode,
    description,
}: z.infer<typeof locationSchema>): Promise<Location> =>
    (
        await apiFetch<DataResponse<Location>>(storeRoute(), {
            body: { short_code: shortCode, description },
        })
    ).data

export const updateLocation = async (
    location: number,
    { shortCode, description }: z.infer<typeof locationSchema>
): Promise<Location> =>
    (
        await apiFetch<DataResponse<Location>>(updateRoute(location), {
            body: { short_code: shortCode, description },
        })
    ).data

export const deleteLocation = async (location: number): Promise<void> => {
    await apiFetch(destroyRoute(location))
}
