import ServerController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/ServerController'
import { useMutation } from '@tanstack/react-query'

import { type DataResponse, apiFetch } from '@/lib/api'

export type ConsoleType = 'novnc' | 'xtermjs'
export type ConsoleSession = App.Data.Server.ConsoleSessionData

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

export const useCreateConsoleSession = () =>
    useMutation({
        mutationFn: ({ server, type }: { server: string; type: ConsoleType }) =>
            createConsoleSession(server, type),
    })
