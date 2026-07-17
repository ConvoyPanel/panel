import type { Node, NodeStatus, PaginatedNodes } from '@/types/node.ts'
import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'
import { hostname } from '@/utils/validation.ts'
import NodeConnectionTestController from '@/wayfinder/actions/App/Http/Controllers/Admin/Nodes/NodeConnectionTestController'
import NodeController from '@/wayfinder/actions/App/Http/Controllers/Admin/Nodes/NodeController'
import NodeStatusController from '@/wayfinder/actions/App/Http/Controllers/Admin/Nodes/NodeStatusController'
import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'
import { useParams } from '@tanstack/react-router'
import { z } from 'zod'

import {
    overagePenaltyFields,
    overagePenaltyPayload,
    refineOveragePenalty,
} from '@/features/bandwidth/overage-penalty.ts'

import { type DataResponse, type PaginatedResponse, apiFetch } from '@/lib/api'
import { queryClient } from '@/lib/query-client.ts'
import {
    rawDataToConnectionResult,
    rawDataToNodeStatus,
} from '@/lib/transformers/node.ts'

export type NodeQueryParams = QueryBuilderParams<
    '*' | 'id' | 'display_name' | 'fqdn' | 'location_id' | 'anchor_id'
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
const statusRoute = NodeStatusController['/api/admin/nodes/{node}/status']
const storeRoute = NodeController.store['/api/admin/nodes']
const updateRoute = NodeController.update['/api/admin/nodes/{node}']
const testConnectionRoute =
    NodeConnectionTestController['/api/admin/nodes/test-connection']

const optionalCredential = z.preprocess(
    value => (value === '' || value == null ? undefined : value),
    z.string().min(1).max(191).optional()
)

export const nodeUpdateSchema = refineOveragePenalty(
    nodeSchema
        .omit({
            rootPrivileges: true,
            privilegeSeparationDisabled: true,
            tokenId: true,
            tokenSecret: true,
        })
        .extend({
            tokenId: optionalCredential,
            tokenSecret: optionalCredential,
            ...overagePenaltyFields,
        })
)

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

const getNodeStatus = async (id: number): Promise<NodeStatus> =>
    rawDataToNodeStatus(
        (await apiFetch<DataResponse<unknown>>(statusRoute(id))).data
    )

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
    status: (id: number | null | undefined) =>
        queryOptions({
            queryKey: [...nodeQueries.details(), id, 'status'] as const,
            queryFn: () => getNodeStatus(id as number),
            enabled: typeof id === 'number',
            refetchInterval: 30_000,
            // An unreachable node is a settled answer, not a blip: the endpoint
            // has already classified why. Retrying a 503 three times with
            // backoff only holds the cards on skeletons for seconds before
            // showing the same cause, and the refetch interval covers recovery.
            retry: false,
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

export const useNodeStatus = (id?: number | null) => {
    const params = useParams({ strict: false }) as { nodeId?: number }
    const nodeId = id ?? params.nodeId

    return useQuery(nodeQueries.status(nodeId))
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

export const updateNode = async (
    id: number,
    payload: z.infer<typeof nodeUpdateSchema>
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

    const body: Record<string, unknown> = {
        display_name: displayName,
        location_id: locationId,
        fqdn,
        port,
        name,
        verify_tls: verifyTls,
        socket_count: socketCount,
        core_count: coreCount,
        cpu_count: cpuCount,
        memory,
        memory_overallocate: memoryOverallocate,
        // Always sent: null is meaningful here (it clears the override back to
        // "inherit"), so this must not be omitted the way the token fields are.
        overage_penalty: overagePenaltyPayload(payload),
    }

    if (tokenId) body.token_id = tokenId
    if (tokenSecret) body.token_secret = tokenSecret

    const res = await apiFetch<DataResponse<Node>>(updateRoute(id), { body })

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
