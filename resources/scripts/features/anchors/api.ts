import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'
import AnchorController from '@/wayfinder/actions/App/Http/Controllers/Admin/AnchorController'
import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'
import type { z } from 'zod'

import { type DataResponse, type PaginatedResponse, apiFetch } from '@/lib/api'

import type {
    Anchor,
    AnchorEnrollment,
    PaginatedAnchors,
    anchorSchema,
} from './types'

export type AnchorQueryParams = QueryBuilderParams<'name' | 'mode'>

const indexRoute = AnchorController.index['/api/admin/anchors']
const showRoute = AnchorController.show['/api/admin/anchors/{anchor}']
const storeRoute = AnchorController.store['/api/admin/anchors']
const updateRoute = AnchorController.update['/api/admin/anchors/{anchor}']
const destroyRoute = AnchorController.destroy['/api/admin/anchors/{anchor}']
const enrollmentRoute =
    AnchorController.enrollment['/api/admin/anchors/{anchor}/enrollment']

export const getAnchors = async (
    params: AnchorQueryParams
): Promise<PaginatedAnchors> => {
    const response = await apiFetch<PaginatedResponse<Anchor>>(indexRoute(), {
        params: withQueryBuilderParams(params),
    })
    return { items: response.items, pagination: response.pagination }
}

export const getAnchor = async (id: number): Promise<Anchor> =>
    (await apiFetch<DataResponse<Anchor>>(showRoute(id))).data

export const anchorQueries = {
    all: () => ['admin', 'anchors'] as const,
    // Only this endpoint fills `nodes`; the list leaves it null.
    detail: (id: number) =>
        queryOptions({
            queryKey: [...anchorQueries.all(), 'detail', id] as const,
            queryFn: () => getAnchor(id),
            refetchInterval: 30_000,
        }),
    list: (params: AnchorQueryParams) =>
        queryOptions({
            queryKey: [...anchorQueries.all(), params] as const,
            queryFn: () => getAnchors(params),
            placeholderData: keepPreviousData,
            refetchInterval: 30_000,
        }),
}

export const useAnchors = (params: AnchorQueryParams) =>
    useQuery(anchorQueries.list(params))

export const useAnchor = (id: number) => useQuery(anchorQueries.detail(id))

const payload = (data: z.infer<typeof anchorSchema>) => ({
    name: data.name,
    mode: data.mode,
    public_url: data.publicUrl,
    panel_url_override:
        data.panelUrlOverride === '' ? null : data.panelUrlOverride,
    relay_id:
        data.mode === 'relay' || data.relayId === 'none'
            ? null
            : Number(data.relayId),
})

export const createAnchor = async (
    data: z.infer<typeof anchorSchema>
): Promise<Anchor> =>
    (
        await apiFetch<DataResponse<Anchor>>(storeRoute(), {
            body: payload(data),
        })
    ).data

export const updateAnchor = async (
    id: number,
    data: z.infer<typeof anchorSchema>
): Promise<Anchor> =>
    (
        await apiFetch<DataResponse<Anchor>>(updateRoute(id), {
            body: payload(data),
        })
    ).data

export const deleteAnchor = async (id: number): Promise<void> => {
    await apiFetch(destroyRoute(id))
}

export const createEnrollment = async (id: number): Promise<AnchorEnrollment> =>
    (await apiFetch<DataResponse<AnchorEnrollment>>(enrollmentRoute(id))).data
