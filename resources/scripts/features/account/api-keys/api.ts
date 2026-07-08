import { queryOptions, useQuery } from '@tanstack/react-query'

import { apiFetch, type DataResponse } from '@/lib/api'
import ApiKeyController from '@/wayfinder/actions/App/Http/Controllers/Client/Account/ApiKeyController'

export interface ApiKey {
    id: number
    name: string
    abilities: string[]
    lastUsedAt: Date | null
}

/** The scope options an end-user can pick when minting a token, mapped to the backend vocabulary. */
export const apiKeyScopes = [
    {
        value: '*',
        label: 'Full access',
        description: 'Full access to your account’s resources.',
    },
    {
        value: 'servers:*',
        label: 'Servers — read & write',
        description: 'View and manage your servers.',
    },
    {
        value: 'servers:read',
        label: 'Servers — read only',
        description: 'View your servers without making changes.',
    },
] as const

export type ApiKeyScope = (typeof apiKeyScopes)[number]['value']

const rawDataToApiKey = (data: App.Data.User.ApiKeyData): ApiKey => ({
    id: data.id,
    name: data.name,
    abilities: data.abilities,
    lastUsedAt: data.lastUsedAt ? new Date(data.lastUsedAt) : null,
})

export const getApiKeys = async (): Promise<ApiKey[]> => {
    const { data } = await apiFetch<DataResponse<App.Data.User.ApiKeyData[]>>(
        ApiKeyController.index()
    )

    return data.map(rawDataToApiKey)
}

/** Creates a token and returns the one-time plaintext value (only ever shown here). */
export const createApiKey = async (
    name: string,
    scope: ApiKeyScope
): Promise<{ key: ApiKey; plainTextToken: string }> => {
    const { data } = await apiFetch<DataResponse<App.Data.User.ApiKeyData>>(
        ApiKeyController.store(),
        { body: { name, abilities: [scope] } }
    )

    return {
        key: rawDataToApiKey(data),
        plainTextToken: data.plainTextToken ?? '',
    }
}

export const deleteApiKey = async (id: number): Promise<void> => {
    await apiFetch(ApiKeyController.destroy(id))
}

export const apiKeyQueries = {
    all: () => ['account', 'api-keys'] as const,
    list: () =>
        queryOptions({ queryKey: apiKeyQueries.all(), queryFn: getApiKeys }),
}

export const useApiKeys = () => useQuery(apiKeyQueries.list())
