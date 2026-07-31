import { NetworkInterface, Vlan } from '@/types/network-interface.ts'
import NetworkInterfaceController from '@/wayfinder/actions/App/Http/Controllers/Admin/Nodes/NetworkInterfaceController'
import VlanController from '@/wayfinder/actions/App/Http/Controllers/Admin/Nodes/VlanController'
import { queryOptions, useQuery } from '@tanstack/react-query'
import { useParams } from '@tanstack/react-router'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'
import {
    rawDataToNetworkInterface,
    rawDataToVlan,
} from '@/lib/transformers/network-interface.ts'

const vlanTagSchema = z.preprocess(
    value => (value === '' || value == null ? null : value),
    z.coerce.number().int().min(1).max(4094).nullable()
)

export const networkInterfaceSchema = z.object({
    name: z.string().min(1).max(40),
    description: z.string().max(191),
    isVlanAware: z.boolean(),
    vlanTag: vlanTagSchema,
})

// NetworkInterfaceController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const indexRoute =
    NetworkInterfaceController.index[
        '/api/admin/nodes/{node}/network-interfaces'
    ]
const storeRoute =
    NetworkInterfaceController.store[
        '/api/admin/nodes/{node}/network-interfaces'
    ]
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
                body: {
                    name: payload.name,
                    description: payload.description,
                    is_vlan_aware: payload.isVlanAware,
                    vlan_tag: payload.vlanTag,
                },
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
                {
                    body: {
                        name: payload.name,
                        description: payload.description,
                        is_vlan_aware: payload.isVlanAware,
                        vlan_tag: payload.vlanTag,
                    },
                }
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

/**
 * VLANs are cached inside their interface rather than under a key of their own,
 * so every VLAN mutation edits the interface list in place. Server counts are
 * left as the API last reported them: declaring or renaming a VLAN never moves
 * a server onto or off it.
 */
export const withVlans =
    (interfaceId: number, update: (vlans: Vlan[]) => Vlan[]) =>
    (interfaces: NetworkInterface[] | undefined) =>
        interfaces?.map(item =>
            item.id === interfaceId
                ? { ...item, vlans: update(item.vlans) }
                : item
        )

const byTag = (a: Vlan, b: Vlan) => a.tag - b.tag

/**
 * Declaring a VLAN can adopt a tag that was already in use, in which case the
 * undeclared entry the list was showing is the same VLAN — replace it rather
 * than end up with the tag twice.
 */
export const upsertVlan = (vlan: Vlan) => (vlans: Vlan[]) =>
    [...vlans.filter(item => item.tag !== vlan.tag), vlan].sort(byTag)

/**
 * Deleting a declaration does not clear the tag off any server, so a VLAN that
 * still has members stays in the list — as undeclared.
 */
export const removeVlan = (vlan: Vlan) => (vlans: Vlan[]) =>
    vlans.flatMap(item => {
        if (item.id !== vlan.id) return [item]

        return item.serversCount > 0
            ? [{ ...item, id: null, name: null, description: null }]
            : []
    })

const vlanStoreRoute =
    VlanController.store[
        '/api/admin/nodes/{node}/network-interfaces/{network_interface}/vlans'
    ]
const vlanUpdateRoute =
    VlanController.update[
        '/api/admin/nodes/{node}/network-interfaces/{network_interface}/vlans/{vlan}'
    ]
const vlanDestroyRoute =
    VlanController.destroy[
        '/api/admin/nodes/{node}/network-interfaces/{network_interface}/vlans/{vlan}'
    ]

export const vlanSchema = z.object({
    tag: z.coerce.number().int().min(1).max(4094),
    name: z.string().max(40),
    description: z.string().max(191),
})

export const createVlan = async (
    nodeId: number,
    interfaceId: number,
    payload: z.infer<typeof vlanSchema>
): Promise<Vlan> =>
    rawDataToVlan(
        (
            await apiFetch<DataResponse<unknown>>(
                vlanStoreRoute({
                    node: nodeId,
                    network_interface: interfaceId,
                }),
                { body: payload }
            )
        ).data
    )

export const updateVlan = async (
    nodeId: number,
    interfaceId: number,
    vlanId: number,
    payload: z.infer<typeof vlanSchema>
): Promise<Vlan> =>
    rawDataToVlan(
        (
            await apiFetch<DataResponse<unknown>>(
                vlanUpdateRoute({
                    node: nodeId,
                    network_interface: interfaceId,
                    vlan: vlanId,
                }),
                { body: payload }
            )
        ).data
    )

export const deleteVlan = async (
    nodeId: number,
    interfaceId: number,
    vlanId: number
): Promise<void> => {
    await apiFetch(
        vlanDestroyRoute({
            node: nodeId,
            network_interface: interfaceId,
            vlan: vlanId,
        })
    )
}
