import { queryOptions, useQuery } from '@tanstack/react-query'

import { apiFetch, type DataResponse } from '@/lib/api'
import OAuthConnectionController from '@/wayfinder/actions/App/Http/Controllers/Client/Account/OAuthConnectionController'

export interface OAuthConnection {
    id: number
    provider: string
    label: string
    name: string | null
    email: string | null
    lastUsedAt: Date | null
    createdAt: Date
}

const rawDataToConnection = (
    data: App.Data.User.OAuthConnectionData
): OAuthConnection => ({
    id: data.id,
    provider: data.provider,
    label: data.label,
    name: data.name,
    email: data.email,
    lastUsedAt: data.lastUsedAt ? new Date(data.lastUsedAt) : null,
    createdAt: new Date(data.createdAt),
})

export const getOAuthConnections = async (): Promise<OAuthConnection[]> => {
    const { data } = await apiFetch<
        DataResponse<App.Data.User.OAuthConnectionData[]>
    >(OAuthConnectionController.index())

    return data.map(rawDataToConnection)
}

export const unlinkOAuthConnection = async (id: number): Promise<void> => {
    await apiFetch(OAuthConnectionController.destroy(id))
}

export const oauthConnectionQueries = {
    all: () => ['account', 'oauth-connections'] as const,
    list: () =>
        queryOptions({
            queryKey: oauthConnectionQueries.all(),
            queryFn: getOAuthConnections,
        }),
}

export const useOAuthConnections = () => useQuery(oauthConnectionQueries.list())
