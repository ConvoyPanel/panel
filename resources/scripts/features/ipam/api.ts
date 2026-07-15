import { useParams } from '@tanstack/react-router'
import {
    keepPreviousData,
    queryOptions,
    useQuery,
} from '@tanstack/react-query'
import { z } from 'zod'

import { rawDataToAddressBlockGroup } from '@/lib/transformers/address-block-group.ts'
import { rawDataToServer } from '@/lib/transformers/server.ts'
import { apiFetch, type DataResponse, type PaginatedResponse } from '@/lib/api'
import { queryClient } from '@/lib/query-client.ts'
import type {
    AddressBlockGroup,
    PaginatedAddressBlockGroups,
} from '@/types/address-block-group.ts'
import type {
    NetworkInterface,
    PaginatedNetworkInterfaces,
} from '@/types/network-interface.ts'
import type { PaginatedServers } from '@/types/server'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'
import AddressBlockGroupController from '@/wayfinder/actions/App/Http/Controllers/Admin/AddressBlockGroupController'

export type AddressBlockGroupQueryParams = QueryBuilderParams<
    '*' | 'name' | 'description' | 'node_id'
>
export type AttachedNodesQueryParams = QueryBuilderParams<'*' | 'id'>
export type CompatibleServerQueryParams = QueryBuilderParams<
    '*' | 'node_id' | 'user_id' | 'name'
>
export type ServerInclude = 'node'

export const addressBlockGroupSchema = z.object({
    name: z.string().min(1).max(40),
    description: z.string().max(191),
})

// AddressBlockGroupController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const indexRoute =
    AddressBlockGroupController.index['/api/admin/address-block-groups']
const showRoute =
    AddressBlockGroupController.show[
        '/api/admin/address-block-groups/{address_block_group}'
    ]
const storeRoute =
    AddressBlockGroupController.store['/api/admin/address-block-groups']
const updateRoute =
    AddressBlockGroupController.update[
        '/api/admin/address-block-groups/{address_block_group}'
    ]
const destroyRoute =
    AddressBlockGroupController.destroy[
        '/api/admin/address-block-groups/{address_block_group}'
    ]
const compatibleServersRoute =
    AddressBlockGroupController.getCompatibleServers[
        '/api/admin/address-block-groups/{address_block_group}/compatible-servers'
    ]
const attachedNodesRoute =
    AddressBlockGroupController.getAttachedNodes[
        '/api/admin/address-block-groups/{address_block_group}/nodes'
    ]
const attachNodeRoute =
    AddressBlockGroupController.attachNode[
        '/api/admin/address-block-groups/{address_block_group}/nodes'
    ]
const detachNodeRoute =
    AddressBlockGroupController.detachNode[
        '/api/admin/address-block-groups/{address_block_group}/nodes/{node}'
    ]

export const getAddressBlockGroups = async (
    params: AddressBlockGroupQueryParams
): Promise<PaginatedAddressBlockGroups> => {
    const res = await apiFetch<PaginatedResponse<AddressBlockGroup>>(
        indexRoute(),
        { params: withQueryBuilderParams(params) }
    )

    return { items: res.items, pagination: res.pagination }
}

const getAddressBlockGroup = async (id: number) =>
    rawDataToAddressBlockGroup(
        (await apiFetch<DataResponse<unknown>>(showRoute(id))).data
    )

const getAttachedNodes = async (
    addressBlockGroupId: number,
    params: AttachedNodesQueryParams
): Promise<PaginatedNetworkInterfaces> => {
    const res = await apiFetch<PaginatedResponse<NetworkInterface>>(
        attachedNodesRoute(addressBlockGroupId),
        { params: withQueryBuilderParams(params) }
    )

    return { items: res.items, pagination: res.pagination }
}

export const getCompatibleServers = async (
    addressBlockGroupId: number,
    params: CompatibleServerQueryParams,
    include?: ServerInclude[]
): Promise<PaginatedServers> => {
    const res = await apiFetch<PaginatedResponse<unknown>>(
        compatibleServersRoute(addressBlockGroupId),
        {
            params: {
                ...withQueryBuilderParams(params),
                include: include?.join(','),
            },
        }
    )

    return {
        items: res.items.map(rawDataToServer),
        pagination: res.pagination,
    }
}

export const addressBlockGroupQueries = {
    all: () => ['admin', 'address-block-groups'] as const,
    lists: () => [...addressBlockGroupQueries.all(), 'list'] as const,
    list: (params: AddressBlockGroupQueryParams) =>
        queryOptions({
            queryKey: [...addressBlockGroupQueries.lists(), params] as const,
            queryFn: () => getAddressBlockGroups(params),
            placeholderData: keepPreviousData,
        }),
    details: () => [...addressBlockGroupQueries.all(), 'detail'] as const,
    detail: (id: number) =>
        queryOptions({
            queryKey: [...addressBlockGroupQueries.details(), id] as const,
            queryFn: () => getAddressBlockGroup(id),
        }),
    nodes: (id: number | null | undefined, params: AttachedNodesQueryParams) =>
        queryOptions({
            queryKey: [
                ...addressBlockGroupQueries.all(),
                id,
                'nodes',
                params,
            ] as const,
            queryFn: () => getAttachedNodes(id!, params),
            enabled: !!id,
            placeholderData: keepPreviousData,
        }),
}

export const useAddressBlockGroups = (params: AddressBlockGroupQueryParams) =>
    useQuery(addressBlockGroupQueries.list(params))

export const preloadAddressBlockGroup = (id: number) =>
    queryClient.prefetchQuery(addressBlockGroupQueries.detail(id))

export const useAddressBlockGroup = () => {
    const params = useParams({ strict: false }) as {
        addressBlockGroupId: number
    }

    return useQuery(addressBlockGroupQueries.detail(params.addressBlockGroupId))
}

export const useAttachedNodes = (
    addressBlockGroupId: number | null | undefined,
    params: AttachedNodesQueryParams
) => useQuery(addressBlockGroupQueries.nodes(addressBlockGroupId, params))

export const createAddressBlockGroup = async (
    payload: z.infer<typeof addressBlockGroupSchema>
) =>
    rawDataToAddressBlockGroup(
        (await apiFetch<DataResponse<unknown>>(storeRoute(), { body: payload }))
            .data
    )

export const updateAddressBlockGroup = async (
    id: number,
    params: z.infer<typeof addressBlockGroupSchema>
) =>
    rawDataToAddressBlockGroup(
        (
            await apiFetch<DataResponse<unknown>>(updateRoute(id), {
                body: params,
            })
        ).data
    )

export const deleteAddressBlockGroup = async (id: number): Promise<void> => {
    await apiFetch(destroyRoute(id))
}

export const attachNode = async (
    addressBlockGroupId: number,
    networkInterfaceId: number
): Promise<void> => {
    await apiFetch(attachNodeRoute(addressBlockGroupId), {
        body: { network_interface_id: networkInterfaceId },
    })
}

export const detachNode = async (
    addressBlockGroupId: number,
    nodeId: number
): Promise<void> => {
    await apiFetch(
        detachNodeRoute({
            address_block_group: addressBlockGroupId,
            node: nodeId,
        })
    )
}
