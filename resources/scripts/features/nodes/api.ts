import { useParams } from '@tanstack/react-router'
import {
    keepPreviousData,
    queryOptions,
    useQuery,
} from '@tanstack/react-query'
import { z } from 'zod'

import { rawDataToConnectionResult } from '@/lib/transformers/node.ts'
import { apiFetch, type DataResponse, type PaginatedResponse } from '@/lib/api'
import { queryClient } from '@/lib/query-client.ts'
import type { Node, PaginatedNodes } from '@/types/node.ts'
import { hostname } from '@/utils/validation.ts'
import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'
import NodeController from '@/wayfinder/actions/App/Http/Controllers/Admin/Nodes/NodeController'
import NodeConnectionTestController from '@/wayfinder/actions/App/Http/Controllers/Admin/Nodes/NodeConnectionTestController'

export type NodeQueryParams = QueryBuilderParams<
    '*' | 'id' | 'display_name' | 'fqdn' | 'location_id' | 'coterm_id'
>

export const nodeSchema = z.object({
    displayName: z
        .string()
        .min(1, 'Display name is required')
        .max(50, "Display name can't exceed 50 characters"),
    locationId: z.coerce.number().positive('Location is required'),
    fqdn: hostname().min(1).max(191),
    port: z.coerce.number().int().min(1, 'Invalid').max(65535, 'Invalid'),
    name: z.string().min(1).max(191),
    verifyTls: z.boolean(),
    tokenId: z.string().min(1).max(191),
    tokenSecret: z.string().min(1).max(191),
    rootPrivileges: z.literal(true),
    privilegeSeparationDisabled: z.literal(true),
    socketCount: z.coerce.number().int().min(1, 'Invalid'),
    coreCount: z.coerce.number().int().min(1, 'Invalid'),
    cpuCount: z.coerce.number().int().min(1, 'Invalid'),
    memory: z.coerce.number().int().min(1, 'Invalid'),
    memoryOverallocate: z.coerce.number().int().min(0, 'Invalid'),
})

// NodeController is served under both the panel (`/api/admin`) and Application
// (`/api/application`) prefixes, so Wayfinder emits URI-keyed dictionaries —
// reference the admin route explicitly.
const indexRoute = NodeController.index['/api/admin/nodes']
const showRoute = NodeController.show['/api/admin/nodes/{node}']
const storeRoute = NodeController.store['/api/admin/nodes']
const testConnectionRoute =
    NodeConnectionTestController['/api/admin/nodes/test-connection']

export const getNodes = async (
    params: NodeQueryParams
): Promise<PaginatedNodes> => {
    const res = await apiFetch<PaginatedResponse<Node>>(indexRoute(), {
        params: withQueryBuilderParams(params),
    })

    return { items: res.items, pagination: res.pagination }
}

const getNode = async (id: number): Promise<Node> =>
    (await apiFetch<DataResponse<Node>>(showRoute(id))).data

export const nodeQueries = {
    all: () => ['admin', 'nodes'] as const,
    lists: () => [...nodeQueries.all(), 'list'] as const,
    list: (params: NodeQueryParams) =>
        queryOptions({
            queryKey: [...nodeQueries.lists(), params] as const,
            queryFn: () => getNodes(params),
            placeholderData: keepPreviousData,
        }),
    details: () => [...nodeQueries.all(), 'detail'] as const,
    detail: (id: number | null | undefined) =>
        queryOptions({
            queryKey: [...nodeQueries.details(), id] as const,
            queryFn: () => getNode(id as number),
            enabled: typeof id === 'number',
        }),
}

export const useNodes = (params: NodeQueryParams) =>
    useQuery(nodeQueries.list(params))

export const preloadNode = (id: number) =>
    queryClient.prefetchQuery(nodeQueries.detail(id))

export const useNode = (id?: number | null) => {
    const params = useParams({ strict: false }) as { nodeId?: number }
    const nodeId = id ?? params.nodeId

    return useQuery(nodeQueries.detail(nodeId))
}

export const createNode = async (
    payload: z.infer<typeof nodeSchema>
): Promise<Node> => {
    const {
        displayName,
        locationId,
        fqdn,
        port,
        name,
        verifyTls,
        tokenId,
        tokenSecret,
        socketCount,
        coreCount,
        cpuCount,
        memory,
        memoryOverallocate,
    } = payload

    const res = await apiFetch<DataResponse<Node>>(storeRoute(), {
        body: {
            display_name: displayName,
            location_id: locationId,
            fqdn,
            port,
            name,
            verify_tls: verifyTls,
            token_id: tokenId,
            token_secret: tokenSecret,
            socket_count: socketCount,
            core_count: coreCount,
            cpu_count: cpuCount,
            memory,
            memory_overallocate: memoryOverallocate,
        },
    })

    return res.data
}

const connectionTestSchema = nodeSchema.pick({
    name: true,
    fqdn: true,
    port: true,
    verifyTls: true,
    tokenId: true,
    tokenSecret: true,
})

export const testConnection = async ({
    name,
    fqdn,
    port,
    verifyTls,
    tokenId,
    tokenSecret,
}: z.infer<typeof connectionTestSchema>) => {
    const { data } = await apiFetch<DataResponse<unknown>>(
        testConnectionRoute(),
        {
            body: {
                name,
                fqdn,
                port,
                verify_tls: verifyTls,
                token_id: tokenId,
                token_secret: tokenSecret,
            },
        }
    )

    return rawDataToConnectionResult(data)
}
