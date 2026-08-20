import {
    BYTES_PER_MB,
    overagePenaltyFields,
    overagePenaltyPayload,
    refineOveragePenalty,
} from '@/features/bandwidth/overage-penalty.ts'
import type { PaginatedServers, Server } from '@/types/server'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http'
import ServerController from '@/wayfinder/actions/App/Http/Controllers/Admin/ServerController'
import {
    keepPreviousData,
    queryOptions,
    useMutation,
    useQuery,
} from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, type PaginatedResponse, apiFetch } from '@/lib/api'
import { queryClient } from '@/lib/query-client.ts'
import { rawDataToServer } from '@/lib/transformers/server.ts'

export type ServerQueryParams = QueryBuilderParams<
    '*' | 'name' | 'hostname' | 'node_id' | 'user_id'
>

const vlanTagSchema = z.preprocess(
    value => (value === '' || value == null ? null : value),
    z.coerce.number().int().min(1).max(4094).nullable()
)

// Entered in decimal MB/s and converted to bytes/s at submit, with -1 for
// uncapped. The column still stores null for that, but the form itself never
// holds a blank: an emptied field used to coerce to 0 and cap every NIC at
// zero, so the sentinel is spelled out the way the backup limits spell theirs.
const speedLimitSchema = z.coerce
    .number()
    .refine(
        value => value === -1 || value >= 1,
        'Use -1 for uncapped, or at least 1 MB/s'
    )

const optionalAccountPasswordSchema = z.preprocess(
    value => (value === '' || value == null ? undefined : value),
    z.string().min(8).max(191).optional()
)

const unlimitedOrNonNegative = z.coerce
    .number()
    .int()
    .min(-1, 'Use -1 for unlimited')

export const serverBuildSchema = refineOveragePenalty(
    z.object({
        cpu: z.coerce.number().int().min(1),
        memory: z.coerce.number().int().min(16),
        disk: z.coerce.number().int().min(1),
        backupCountLimit: unlimitedOrNonNegative,
        backupSizeLimit: unlimitedOrNonNegative,
        bandwidthLimit: unlimitedOrNonNegative,
        bandwidthUsage: z.coerce.number().int().min(0),
        speedLimit: speedLimitSchema,
        ...overagePenaltyFields,
    })
)

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
        // Mebibytes, converted to bytes at submit; -1 is unmetered.
        bandwidth: unlimitedOrNonNegative,
        speedLimit: speedLimitSchema,

        // Optional secondary/data disks, each on its own storage. The primary
        // OS disk is `storageId` + `disk` above; these become `limits.disks[]`.
        // `size` is entered in GiB and converted to bytes at submit time.
        disks: z
            .array(
                z.object({
                    storageId: z
                        .string({ error: 'Storage is required.' })
                        .min(1),
                    size: z.coerce.number().min(1),
                })
            )
            .optional(),

        // backup limits
        backupCount: z.coerce.number().min(-1),
        backupSize: z.coerce.number().min(-1),

        // IP addresses
        networkInterfaceId: z
            .string({ error: 'Network Interface is required.' })
            .min(1),
        vlanTag: vlanTagSchema,
        addressesIpv4Count: z.coerce.number().min(0).max(100).optional(),
        addressesIpv6Count: z.coerce.number().min(0).max(100).optional(),
        addresses: z.array(z.string()).optional(),

        // Server creation options
        deferredOsSelection: z.boolean(),
        shouldCreateVm: z.boolean(),
        accountPassword: optionalAccountPasswordSchema,
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
const updateBuildRoute =
    ServerController.updateBuild['/api/admin/servers/{server}/settings/build']

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

const unflagRoute =
    ServerController.unflag['/api/admin/servers/{server}/settings/unflag']

/**
 * Clears a placement flag once the operator has resolved it. The flag blocks
 * network sync while it stands, so this is the escape hatch for flags no
 * automatic re-home will clear (e.g. an investigated SMBIOS mismatch).
 */
export const useUnflagServer = () =>
    useMutation({
        mutationFn: (uuid: string) => apiFetch(unflagRoute(uuid)),
        onSuccess: () =>
            queryClient.invalidateQueries({ queryKey: serverQueries.all() }),
    })

export const preloadServer = (id: number) =>
    queryClient.prefetchQuery(serverQueries.detail(id))

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
    speedLimit,
    disks,
    backupCount,
    backupSize,
    networkInterfaceId,
    vlanTag,
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
                        // -1 (uncapped) is stored as a null column.
                        speed_limit:
                            speedLimit === -1
                                ? null
                                : Math.round(speedLimit * BYTES_PER_MB),
                        disks: disks?.map(d => ({
                            storage_id: Number(d.storageId),
                            size: d.size,
                        })),
                        backups: {
                            count: backupCount,
                            size: backupSize,
                        },
                        network_interface_id: Number(networkInterfaceId),
                        vlan_tag: vlanTag,
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

const MEBIBYTE = 1024 * 1024

export const updateServerBuild = async (
    serverId: number,
    payload: z.infer<typeof serverBuildSchema>
): Promise<Server> => {
    const res = await apiFetch<DataResponse<unknown>>(
        updateBuildRoute(String(serverId)),
        {
            body: {
                cpu: payload.cpu,
                memory: payload.memory * MEBIBYTE,
                disk: payload.disk * MEBIBYTE,
                backup_count_limit: payload.backupCountLimit,
                backup_size_limit:
                    payload.backupSizeLimit === -1
                        ? -1
                        : payload.backupSizeLimit * MEBIBYTE,
                bandwidth_limit:
                    payload.bandwidthLimit === -1
                        ? -1
                        : payload.bandwidthLimit * MEBIBYTE,
                bandwidth_usage: payload.bandwidthUsage * MEBIBYTE,
                speed_limit:
                    payload.speedLimit === -1
                        ? null
                        : Math.round(payload.speedLimit * BYTES_PER_MB),
                // Always send this field: null explicitly clears the server-level
                // override and returns it to the node -> global cascade.
                overage_penalty: overagePenaltyPayload(payload),
            },
        }
    )

    return rawDataToServer(res.data)
}
