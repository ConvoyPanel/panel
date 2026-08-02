import ServerController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/ServerController'
import SettingsController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/SettingsController'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { type DataResponse, apiFetch } from '@/lib/api'

export type ConsoleType = 'novnc' | 'xtermjs'
export type ConsoleSession = App.Data.Server.ConsoleSessionData
export type SerialConsole = App.Data.Server.SerialConsoleData

export const createConsoleSession = async (
    server: string,
    type: ConsoleType
): Promise<ConsoleSession> =>
    (
        await apiFetch<DataResponse<ConsoleSession>>(
            ServerController.createConsoleSession(server),
            { body: { type } }
        )
    ).data

export const getSerialConsole = async (uuid: string): Promise<SerialConsole> =>
    (
        await apiFetch<DataResponse<SerialConsole>>(
            SettingsController.getSerialConsole(uuid)
        )
    ).data

export const enableSerialConsole = async (
    uuid: string
): Promise<SerialConsole> =>
    (
        await apiFetch<DataResponse<SerialConsole>>(
            SettingsController.enableSerialConsole(uuid)
        )
    ).data

export const consoleQueries = {
    serial: (uuid: string) =>
        queryOptions({
            queryKey: ['servers', uuid, 'serial-console'] as const,
            queryFn: () => getSerialConsole(uuid),
            // Reaches the node, and the answer only changes when someone adds
            // the device or reboots — no reason to refetch on every focus.
            staleTime: 60_000,
            retry: false,
        }),
}

export const useSerialConsole = (uuid: string, enabled = true) =>
    useQuery({ ...consoleQueries.serial(uuid), enabled })
