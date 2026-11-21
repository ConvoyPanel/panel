import {
    expiresAtPayload,
    tokenExpirySchema,
} from '@/features/tokens/expiry.ts'
import ApiKeyController from '@/wayfinder/actions/App/Http/Controllers/Client/Account/ApiKeyController'
import { queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'

export interface ApiKey {
    id: number
    name: string
    abilities: string[]
    lastUsedAt: Date | null
    /** Null for a token that never expires. */
    expiresAt: Date | null
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

export const apiKeyCreateSchema = z.object({
    name: z.string().min(1).max(191),
    scope: z.enum(
        apiKeyScopes.map(s => s.value) as [ApiKeyScope, ...ApiKeyScope[]]
    ),
    expiresIn: tokenExpirySchema,
})

export type ApiKeyCreateInput = z.infer<typeof apiKeyCreateSchema>

const rawDataToApiKey = (data: App.Data.User.ApiKeyData): ApiKey => ({
    id: data.id,
    name: data.name,
    abilities: data.abilities,
    lastUsedAt: data.lastUsedAt ? new Date(data.lastUsedAt) : null,
    expiresAt: data.expiresAt ? new Date(data.expiresAt) : null,
})

export const getApiKeys = async (): Promise<ApiKey[]> => {
    const { data } = await apiFetch<DataResponse<App.Data.User.ApiKeyData[]>>(
        ApiKeyController.index()
    )

    return data.map(rawDataToApiKey)
}

/** Creates a token and returns the one-time plaintext value (only ever shown here). */
export const createApiKey = async ({
    name,
    scope,
    expiresIn,
}: ApiKeyCreateInput): Promise<{ key: ApiKey; plainTextToken: string }> => {
    const { data } = await apiFetch<DataResponse<App.Data.User.ApiKeyData>>(
        ApiKeyController.store(),
        { body: { name, abilities: [scope], ...expiresAtPayload(expiresIn) } }
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
