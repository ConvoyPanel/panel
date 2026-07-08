import {
    queryOptions,
    useMutation,
    useQuery,
    useQueryClient,
} from '@tanstack/react-query'

import { apiFetch, type DataResponse } from '@/lib/api'
import ServerDiskController from '@/wayfinder/actions/App/Http/Controllers/Admin/ServerDiskController'

export type ServerDisk = App.Data.Server.ServerDiskData

// ServerDiskController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin routes explicitly.
const indexRoute =
    ServerDiskController.index['/api/admin/servers/{server}/disks']
const storeRoute =
    ServerDiskController.store['/api/admin/servers/{server}/disks']
const updateRoute =
    ServerDiskController.update['/api/admin/servers/{server}/disks/{disk}']
const destroyRoute =
    ServerDiskController.destroy['/api/admin/servers/{server}/disks/{disk}']

// The `{server}` route binds by id, uuid_short, or uuid (see the AppServiceProvider
// route binding), so a numeric admin serverId works directly. Sizes are bytes.
type ServerRef = string | number

export const diskQueries = {
    all: (server: ServerRef) => ['admin', 'servers', server, 'disks'] as const,

    list: (server: ServerRef) =>
        queryOptions({
            queryKey: diskQueries.all(server),
            // The index endpoint returns a bare array (no `data` envelope).
            queryFn: () =>
                apiFetch<ServerDisk[]>(indexRoute(String(server))),
        }),
}

export const useServerDisks = (server: ServerRef) =>
    useQuery(diskQueries.list(server))

export interface AddDiskInput {
    storageId: number
    // Bytes. PVE allocates in integer GiB, so callers should pass a GiB-aligned
    // byte count.
    size: number
}

export const useAddServerDisk = (server: ServerRef) => {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: async ({ storageId, size }: AddDiskInput) =>
            (
                await apiFetch<DataResponse<ServerDisk>>(
                    storeRoute(String(server)),
                    { body: { storage_id: storageId, size } }
                )
            ).data,
        onSuccess: () =>
            queryClient.invalidateQueries({
                queryKey: diskQueries.all(server),
            }),
    })
}

export const useResizeServerDisk = (server: ServerRef) => {
    const queryClient = useQueryClient()

    return useMutation({
        // `size` is the new total size in bytes (grow only; the backend rejects
        // a shrink with `cannot_shrink_disk`).
        mutationFn: async ({ diskId, size }: { diskId: number; size: number }) =>
            (
                await apiFetch<DataResponse<ServerDisk>>(
                    updateRoute([String(server), diskId]),
                    { body: { size } }
                )
            ).data,
        onSuccess: () =>
            queryClient.invalidateQueries({
                queryKey: diskQueries.all(server),
            }),
    })
}

export const useRemoveServerDisk = (server: ServerRef) => {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: (diskId: number) =>
            apiFetch(destroyRoute([String(server), diskId])),
        onSuccess: () =>
            queryClient.invalidateQueries({
                queryKey: diskQueries.all(server),
            }),
    })
}
