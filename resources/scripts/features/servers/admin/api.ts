import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { rawDataToServer } from '@/api/transformers/server.ts'
import { apiFetch, type DataResponse, type PaginatedResponse } from '@/lib/api'
import type { PaginatedServers, Server } from '@/types/server'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http'
import ServerController from '@/wayfinder/actions/App/Http/Controllers/Admin/ServerController'

export type ServerQueryParams = QueryBuilderParams<
    '*' | 'name' | 'hostname' | 'node_id' | 'user_id'
>

export const serverSchema = z
    .object({
        name: z.string().min(1, 'Name is required.').max(191),
        hostname: z.string().min(1, 'Hostname is required.').max(191),
        nodeId: z.string({ error: 'Node is required.' }).min(1),
        storageId: z.string({ error: 'Storage is required.' }).min(1),
        userId: z.string({ error: 'User is required.' }).min(1),
        vmid: z.string().optional(),

        // limits
        cpu: z.coerce.number().min(1).max(100000),
        memory: z.coerce.number().min(128).max(1048576),
        disk: z.coerce.number().min(1).max(10485760),
        bandwidth: z.coerce.number().min(0).optional(),

        // backup limits
        backupCount: z.coerce.number().min(-1),
        backupSize: z.coerce.number().min(-1),

        // IP addresses
        networkInterfaceId: z
            .string({ error: 'Network Interface is required.' })
            .min(1),
        addressesIpv4Count: z.coerce.number().min(0).max(100).optional(),
        addressesIpv6Count: z.coerce.number().min(0).max(100).optional(),
        addresses: z.array(z.string()).optional(),

        // Server creation options
        deferredOsSelection: z.boolean(),
        shouldCreateVm: z.boolean(),
        accountPassword: z.string().min(8).max(191).optional(),
        templateUuid: z.string().optional(),
        startOnCompletion: z.boolean(),
    })
    .superRefine((data, ctx) => {
        if (data.shouldCreateVm && !data.deferredOsSelection) {
            if (!data.accountPassword) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    path: ['accountPassword'],
                    message: 'Password is required.',
                })
            }
            if (!data.templateUuid) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    path: ['templateUuid'],
                    message: 'Template is required.',
                })
            }
        }

        // Allow zero addresses; no additional validation required here.
    })

// The admin ServerController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const indexRoute = ServerController.index['/api/admin/servers']
const showRoute = ServerController.show['/api/admin/servers/{server}']
const storeRoute = ServerController.store['/api/admin/servers']

export const getServers = async (
    params: ServerQueryParams
): Promise<PaginatedServers> => {
    const res = await apiFetch<PaginatedResponse<Server>>(indexRoute(), {
        params: withQueryBuilderParams(params),
    })

    return {
        items: res.items.map(rawDataToServer),
        pagination: res.pagination,
    }
}

export const getServer = async (id: number) =>
    rawDataToServer(
        (await apiFetch<DataResponse<unknown>>(showRoute(String(id)))).data
    )

export const serverQueries = {
    all: () => ['admin', 'servers'] as const,
    lists: () => [...serverQueries.all(), 'list'] as const,
    list: (params: ServerQueryParams) =>
        queryOptions({
            queryKey: [...serverQueries.lists(), params] as const,
            queryFn: () => getServers(params),
            placeholderData: keepPreviousData,
        }),
    details: () => [...serverQueries.all(), 'detail'] as const,
    detail: (id: number | null | undefined) =>
        queryOptions({
            queryKey: [...serverQueries.details(), id] as const,
            queryFn: () => getServer(id!),
            enabled: id != null,
        }),
}

export const useServers = (params: ServerQueryParams) =>
    useQuery(serverQueries.list(params))

export const useServer = (id: number | null) =>
    useQuery(serverQueries.detail(id))

export const createServer = async ({
    name,
    hostname,
    nodeId,
    storageId,
    userId,
    vmid,
    cpu,
    memory,
    disk,
    bandwidth,
    backupCount,
    backupSize,
    networkInterfaceId,
    addressesIpv4Count,
    addressesIpv6Count,
    addresses,
    deferredOsSelection,
    shouldCreateVm,
    accountPassword,
    templateUuid,
    startOnCompletion,
}: z.infer<typeof serverSchema>) =>
    rawDataToServer(
        (
            await apiFetch<DataResponse<unknown>>(storeRoute(), {
                body: {
                    name,
                    hostname,
                    node_id: Number(nodeId),
                    storage_id: Number(storageId),
                    user_id: Number(userId),
                    vmid: vmid ? Number(vmid) : undefined,
                    limits: {
                        cpu,
                        memory,
                        disk,
                        bandwidth,
                        backups: {
                            count: backupCount,
                            size: backupSize,
                        },
                        network_interface_id: Number(networkInterfaceId),
                        addresses_ipv4_count: addressesIpv4Count,
                        addresses_ipv6_count: addressesIpv6Count,
                        addresses: addresses?.map(Number),
                    },
                    deferred_os_selection: deferredOsSelection,
                    should_create_vm: shouldCreateVm,
                    account_password: accountPassword,
                    template_uuid: templateUuid,
                    start_on_completion: startOnCompletion,
                },
            })
        ).data
    )
