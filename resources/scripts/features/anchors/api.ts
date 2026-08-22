import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'
import AnchorEnrollmentController from '@/wayfinder/actions/App/Http/Controllers/Admin/AnchorEnrollmentController'
import AnchorEnrollmentKeyController from '@/wayfinder/actions/App/Http/Controllers/Admin/AnchorEnrollmentKeyController'
import NodeController from '@/wayfinder/actions/App/Http/Controllers/Admin/Nodes/NodeController'
import RelayController from '@/wayfinder/actions/App/Http/Controllers/Admin/RelayController'
import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'
import type { z } from 'zod'

import { type DataResponse, type PaginatedResponse, apiFetch } from '@/lib/api'

import type {
    AnchorEnrollment,
    AnchorEnrollmentQueueItem,
    EnrollmentKey,
    PaginatedEnrollmentKeys,
    PaginatedEnrollments,
    PaginatedRelays,
    Relay,
    enrollmentKeySchema,
    relaySchema,
} from './types'

/*
 * Three resources, one module, because they are one workflow: cut a key, watch
 * the machine arrive, let it in. Relays ride along because they are the same
 * daemon in its other role.
 *
 * NodeController is served under both /api/admin and /api/application, so
 * Wayfinder emits URI-keyed dicts -- reference the admin URI explicitly.
 */
const enrollments = {
    index: AnchorEnrollmentController.index['/api/admin/anchors/enrollments'],
    show: AnchorEnrollmentController.show[
        '/api/admin/anchors/enrollments/{anchor_enrollment}'
    ],
    approve:
        AnchorEnrollmentController.approve[
            '/api/admin/anchors/enrollments/{anchor_enrollment}/approve'
        ],
    destroy:
        AnchorEnrollmentController.destroy[
            '/api/admin/anchors/enrollments/{anchor_enrollment}'
        ],
}

const keys = {
    index: AnchorEnrollmentKeyController.index[
        '/api/admin/anchors/enrollment-keys'
    ],
    store: AnchorEnrollmentKeyController.store[
        '/api/admin/anchors/enrollment-keys'
    ],
    revoke: AnchorEnrollmentKeyController.revoke[
        '/api/admin/anchors/enrollment-keys/{enrollment_key}/revoke'
    ],
    destroy:
        AnchorEnrollmentKeyController.destroy[
            '/api/admin/anchors/enrollment-keys/{enrollment_key}'
        ],
}

export type EnrollmentQueryParams = QueryBuilderParams<'name' | 'mode'>
export type RelayQueryParams = QueryBuilderParams<'name'>

export const getEnrollments = async (
    params: EnrollmentQueryParams
): Promise<PaginatedEnrollments> => {
    const response = await apiFetch<
        PaginatedResponse<AnchorEnrollmentQueueItem>
    >(enrollments.index(), { params: withQueryBuilderParams(params) })

    return { items: response.items, pagination: response.pagination }
}

export const getEnrollment = async (
    id: number
): Promise<AnchorEnrollmentQueueItem> =>
    (
        await apiFetch<DataResponse<AnchorEnrollmentQueueItem>>(
            enrollments.show(id)
        )
    ).data

export const enrollmentQueries = {
    all: () => ['admin', 'anchor-enrollments'] as const,
    detail: (id: number) =>
        queryOptions({
            queryKey: [...enrollmentQueries.all(), 'detail', id] as const,
            queryFn: () => getEnrollment(id),
        }),
    list: (params: EnrollmentQueryParams) =>
        queryOptions({
            queryKey: [...enrollmentQueries.all(), params] as const,
            queryFn: () => getEnrollments(params),
            placeholderData: keepPreviousData,
            // The whole point of this screen is watching for an arrival, so it
            // polls faster than a roster nobody is waiting on.
            refetchInterval: 5_000,
        }),
}

export const useEnrollments = (params: EnrollmentQueryParams) =>
    useQuery(enrollmentQueries.list(params))

export const useEnrollment = (id: number) =>
    useQuery(enrollmentQueries.detail(id))

export const approveEnrollment = async (
    id: number,
    body: Record<string, unknown>
): Promise<{ id: number }> =>
    (await apiFetch<DataResponse<{ id: number }>>(enrollments.approve(id), { body }))
        .data

export const rejectEnrollment = async (id: number): Promise<void> => {
    await apiFetch(enrollments.destroy(id))
}

/* Enrollment keys ------------------------------------------------------- */

export const getEnrollmentKeys = async (): Promise<PaginatedEnrollmentKeys> => {
    const response = await apiFetch<PaginatedResponse<EnrollmentKey>>(
        keys.index()
    )

    return { items: response.items, pagination: response.pagination }
}

export const enrollmentKeyQueries = {
    all: () => ['admin', 'anchor-enrollment-keys'] as const,
    list: () =>
        queryOptions({
            queryKey: enrollmentKeyQueries.all(),
            queryFn: getEnrollmentKeys,
            placeholderData: keepPreviousData,
        }),
}

export const useEnrollmentKeys = () => useQuery(enrollmentKeyQueries.list())

/**
 * `null` means unlimited / never expires, and has to be asked for by name --
 * omitting the field gives the safe shape instead.
 */
const optionalNumber = (value: string): number | null =>
    value === 'unlimited' || value === 'never' ? null : Number(value)

export const createEnrollmentKey = async (
    data: z.infer<typeof enrollmentKeySchema>
): Promise<EnrollmentKey> =>
    (
        await apiFetch<DataResponse<EnrollmentKey>>(keys.store(), {
            body: {
                name: data.name,
                mode: data.mode === 'any' ? null : data.mode,
                max_uses: optionalNumber(data.maxUses),
                expires_in_minutes: optionalNumber(data.expiresInMinutes),
            },
        })
    ).data

export const revokeEnrollmentKey = async (
    id: number
): Promise<EnrollmentKey> =>
    (await apiFetch<DataResponse<EnrollmentKey>>(keys.revoke(id))).data

export const deleteEnrollmentKey = async (id: number): Promise<void> => {
    await apiFetch(keys.destroy(id))
}

/* Relays ---------------------------------------------------------------- */

export const getRelays = async (
    params: RelayQueryParams
): Promise<PaginatedRelays> => {
    const response = await apiFetch<PaginatedResponse<Relay>>(
        RelayController.index['/api/admin/relays'](),
        { params: withQueryBuilderParams(params) }
    )

    return { items: response.items, pagination: response.pagination }
}

export const relayQueries = {
    all: () => ['admin', 'relays'] as const,
    list: (params: RelayQueryParams) =>
        queryOptions({
            queryKey: [...relayQueries.all(), params] as const,
            queryFn: () => getRelays(params),
            placeholderData: keepPreviousData,
            refetchInterval: 30_000,
        }),
}

export const useRelays = (params: RelayQueryParams = {}) =>
    useQuery(relayQueries.list(params))

const relayPayload = (data: z.infer<typeof relaySchema>) => ({
    name: data.name,
    public_url: data.publicUrl,
    panel_url_override:
        data.panelUrlOverride === '' ? null : data.panelUrlOverride,
})

export const createRelay = async (
    data: z.infer<typeof relaySchema>
): Promise<Relay> =>
    (
        await apiFetch<DataResponse<Relay>>(
            RelayController.store['/api/admin/relays'](),
            { body: relayPayload(data) }
        )
    ).data

export const updateRelay = async (
    id: number,
    data: z.infer<typeof relaySchema>
): Promise<Relay> =>
    (
        await apiFetch<DataResponse<Relay>>(
            RelayController.update['/api/admin/relays/{relay}'](id),
            { body: relayPayload(data) }
        )
    ).data

export const deleteRelay = async (id: number): Promise<void> => {
    await apiFetch(RelayController.destroy['/api/admin/relays/{relay}'](id))
}

export const createRelayEnrollment = async (
    id: number
): Promise<AnchorEnrollment> =>
    (
        await apiFetch<DataResponse<AnchorEnrollment>>(
            RelayController.enrollment['/api/admin/relays/{relay}/enrollment'](
                id
            )
        )
    ).data

/**
 * A fresh install command for the agent on an existing node.
 *
 * Serves both installing one where there has never been one (a node carried
 * over from v4) and re-keying one that is already there.
 */
export const createNodeAgentEnrollment = async (
    id: number
): Promise<AnchorEnrollment> =>
    (
        await apiFetch<DataResponse<AnchorEnrollment>>(
            NodeController.agentEnrollment[
                '/api/admin/nodes/{node}/agent/enrollment'
            ](id)
        )
    ).data
