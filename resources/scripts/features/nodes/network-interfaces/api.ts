import { useParams } from '@tanstack/react-router'
import { queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { rawDataToNetworkInterface } from '@/lib/transformers/network-interface.ts'
import { apiFetch, type DataResponse } from '@/lib/api'
import { NetworkInterface } from '@/types/network-interface.ts'
import NetworkInterfaceController from '@/wayfinder/actions/App/Http/Controllers/Admin/Nodes/NetworkInterfaceController'

export const networkInterfaceSchema = z.object({
    name: z.string().min(1).max(40),
    description: z.string().max(191),
})

// NetworkInterfaceController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const indexRoute =
    NetworkInterfaceController.index['/api/admin/nodes/{node}/network-interfaces']
const storeRoute =
    NetworkInterfaceController.store['/api/admin/nodes/{node}/network-interfaces']
const updateRoute =
    NetworkInterfaceController.update[
        '/api/admin/nodes/{node}/network-interfaces/{network_interface}'
    ]
const destroyRoute =
    NetworkInterfaceController.destroy[
        '/api/admin/nodes/{node}/network-interfaces/{network_interface}'
    ]

export const getNetworkInterfaces = async (
    nodeId: number
): Promise<NetworkInterface[]> => {
    const { data } = await apiFetch<DataResponse<any[]>>(indexRoute(nodeId))

    return data.map(rawDataToNetworkInterface)
}

export const networkInterfaceQueries = {
    all: (nodeId: number) =>
        ['admin', 'nodes', nodeId, 'network-interfaces'] as const,
    list: (nodeId: number) =>
        queryOptions({
            queryKey: networkInterfaceQueries.all(nodeId),
            queryFn: () => getNetworkInterfaces(nodeId),
            enabled: !!nodeId,
        }),
}

export const useNetworkInterfaces = (id?: number | null) => {
    const { nodeId: routeNodeId } = useParams({ strict: false })
    const nodeId = id ?? Number(routeNodeId)

    return useQuery(networkInterfaceQueries.list(nodeId))
}

export const createNetworkInterface = async (
    nodeId: number,
    payload: z.infer<typeof networkInterfaceSchema>
) =>
    rawDataToNetworkInterface(
        (
            await apiFetch<DataResponse<unknown>>(storeRoute(nodeId), {
                body: payload,
            })
        ).data
    )

export const updateNetworkInterface = async (
    nodeId: number,
    interfaceId: number,
    payload: z.infer<typeof networkInterfaceSchema>
) =>
    rawDataToNetworkInterface(
        (
            await apiFetch<DataResponse<unknown>>(
                updateRoute({ node: nodeId, network_interface: interfaceId }),
                { body: payload }
            )
        ).data
    )

export const deleteNetworkInterface = async (
    nodeId: number,
    interfaceId: number
): Promise<void> => {
    await apiFetch(
        destroyRoute({ node: nodeId, network_interface: interfaceId })
    )
}
