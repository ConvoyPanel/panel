import { queryOptions, useQuery } from '@tanstack/react-query'

import { apiFetch, type DataResponse } from '@/lib/api'
import SessionRecordController from '@/wayfinder/actions/App/Http/Controllers/Client/Account/SessionRecordController'

export interface Session {
    id: number
    ipAddress: string | null
    device: string
    lastActiveAt: Date
    isCurrent: boolean
}

/** Best-effort "Browser on OS" label from a user-agent string, for display only. */
export const deviceLabel = (userAgent: string | null): string => {
    if (!userAgent) return 'Unknown device'

    const browser =
        /Edg/.test(userAgent) ? 'Edge'
        : /OPR|Opera/.test(userAgent) ? 'Opera'
        : /Firefox/.test(userAgent) ? 'Firefox'
        : /Chrome/.test(userAgent) ? 'Chrome'
        : /Safari/.test(userAgent) ? 'Safari'
        : 'Browser'

    const os =
        /Windows/.test(userAgent) ? 'Windows'
        : /Mac OS X|Macintosh/.test(userAgent) ? 'macOS'
        : /Android/.test(userAgent) ? 'Android'
        : /iPhone|iPad|iOS/.test(userAgent) ? 'iOS'
        : /Linux/.test(userAgent) ? 'Linux'
        : null

    return os ? `${browser} on ${os}` : browser
}

const rawDataToSession = (data: App.Data.User.SessionRecordData): Session => ({
    id: data.id,
    ipAddress: data.ipAddress,
    device: deviceLabel(data.userAgent),
    lastActiveAt: new Date(data.lastActiveAt),
    isCurrent: data.isCurrent,
})

export const getSessions = async (): Promise<Session[]> => {
    const { data } = await apiFetch<
        DataResponse<App.Data.User.SessionRecordData[]>
    >(SessionRecordController.index())

    return data.map(rawDataToSession)
}

export const revokeSession = async (id: number): Promise<void> => {
    await apiFetch(SessionRecordController.destroy(id))
}

export const sessionQueries = {
    all: () => ['account', 'sessions'] as const,
    list: () =>
        queryOptions({ queryKey: sessionQueries.all(), queryFn: getSessions }),
}

export const useSessions = () => useQuery(sessionQueries.list())
