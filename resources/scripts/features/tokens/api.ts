import {
    keepPreviousData,
    queryOptions,
    useQuery,
} from '@tanstack/react-query'
import { z } from 'zod'

import {
    buildAbilities,
    TOKEN_RESOURCES,
    type ApiKey,
    type PaginatedApiKeys,
} from '@/features/tokens/types.ts'
import { apiFetch, type DataResponse, type PaginatedResponse } from '@/lib/api'
import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'
import TokenController from '@/wayfinder/actions/App/Http/Controllers/Admin/TokenController'

// TokenController is served on both the panel (`/api/admin`) and the Application
// token API (`/api/application`), so Wayfinder emits URI-keyed dictionaries
// instead of callables — the panel references the admin route.
const indexRoute = TokenController.index['/api/admin/tokens']
const storeRoute = TokenController.store['/api/admin/tokens']
const destroyRoute = TokenController.destroy['/api/admin/tokens/{token}']

export type TokenQueryParams = QueryBuilderParams

export const tokenScopeSchema = z.enum(['none', 'read', 'write'])

export const tokenSchema = z
    .object({
        name: z.string().min(1).max(191),
        fullAccess: z.boolean(),
        scopes: z.record(z.enum(TOKEN_RESOURCES), tokenScopeSchema),
    })
    .refine(
        data =>
            data.fullAccess ||
            Object.values(data.scopes).some(access => access !== 'none'),
        { message: 'Grant at least one ability.', path: ['scopes'] }
    )

export type TokenInput = z.infer<typeof tokenSchema>

export const getTokens = async (
    params: TokenQueryParams
): Promise<PaginatedApiKeys> => {
    const res = await apiFetch<PaginatedResponse<ApiKey>>(indexRoute(), {
        params: withQueryBuilderParams(params),
    })

    return { items: res.items, pagination: res.pagination }
}

/** Creates a token and returns it with the one-time plaintext value. */
export const createToken = async (input: TokenInput): Promise<ApiKey> =>
    (
        await apiFetch<DataResponse<ApiKey>>(storeRoute(), {
            body: {
                name: input.name.trim(),
                abilities: buildAbilities(input.fullAccess, input.scopes),
            },
        })
    ).data

export const deleteToken = async (id: number): Promise<void> => {
    await apiFetch(destroyRoute(id))
}

export const tokenQueries = {
    all: () => ['admin', 'tokens'] as const,
    lists: () => [...tokenQueries.all(), 'list'] as const,
    list: (params: TokenQueryParams) =>
        queryOptions({
            queryKey: [...tokenQueries.lists(), params] as const,
            queryFn: () => getTokens(params),
            placeholderData: keepPreviousData,
        }),
}

export const useTokens = (params: TokenQueryParams) =>
    useQuery(tokenQueries.list(params))
