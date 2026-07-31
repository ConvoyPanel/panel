import {
    queryOptions,
    useMutation,
    useQueryClient,
} from '@tanstack/react-query'

import { apiFetch, type DataResponse } from '@/lib/api'
import ServerController from '@/wayfinder/actions/App/Http/Controllers/Admin/ServerController'

export type ServerStateData = App.Data.Server.Proxmox.ServerStateData

export type PowerAction = 'start' | 'shutdown' | 'kill' | 'restart'

// The admin ServerController is also exposed on the Application (token) API, so
// Wayfinder emits a URI-keyed dictionary rather than a callable — the panel
// uses the /api/admin route.
const getStateRoute = ServerController.getState['/api/admin/servers/{server}/state']
const sendPowerCommandRoute =
    ServerController.sendPowerCommand['/api/admin/servers/{server}/power']

export const serverStateQueries = {
    all: (uuid: string) => ['admin', 'servers', uuid, 'state'] as const,

    detail: (uuid: string) =>
        queryOptions({
            queryKey: serverStateQueries.all(uuid),
            queryFn: async () =>
                (
                    await apiFetch<DataResponse<ServerStateData>>(
                        getStateRoute(uuid)
                    )
                ).data,
        }),
}

export const useSendPowerCommand = (uuid: string) => {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: (command: PowerAction) =>
            apiFetch(sendPowerCommandRoute(uuid), { body: { command } }),
        onSuccess: () =>
            queryClient.invalidateQueries({
                queryKey: serverStateQueries.all(uuid),
            }),
    })
}
