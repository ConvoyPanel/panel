import {
    type ApiKey,
    type PaginatedApiKeys,
    TOKEN_RESOURCES,
    buildAbilities,
} from '@/features/tokens/types.ts'
import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'
import TokenController from '@/wayfinder/actions/App/Http/Controllers/Admin/TokenController'
import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, type PaginatedResponse, apiFetch } from '@/lib/api'

// TokenController is served on both the panel (`/api/admin`) and the Application
// token API (`/api/application`), so Wayfinder emits URI-keyed dictionaries
// instead of callables — the panel references the admin route.
const indexRoute = TokenController.index['/api/admin/tokens']
const storeRoute = TokenController.store['/api/admin/tokens']
const updateRoute = TokenController.update['/api/admin/tokens/{token}']
const destroyRoute = TokenController.destroy['/api/admin/tokens/{token}']

export type TokenQueryParams = QueryBuilderParams

export const tokenScopeSchema = z.enum(['none', 'read', 'write'])

const networkRuleSchema = z.union([z.ipv4(), z.ipv6(), z.cidrv4(), z.cidrv6()])

export const parseAllowedNetworks = (value: string): string[] =>
    Array.from(
        new Set(
            value
                .split(/\r?\n/)
                .map(rule => rule.trim())
                .filter(Boolean)
        )
    )

export const allowedNetworksSchema = z.string().superRefine((value, ctx) => {
    const rules = parseAllowedNetworks(value)

    if (rules.length > 100) {
        ctx.addIssue({
            code: 'custom',
            message: 'Enter no more than 100 addresses or CIDR ranges.',
        })
    }

    const invalid = rules.findIndex(
        rule => !networkRuleSchema.safeParse(rule).success
    )
    if (invalid !== -1) {
        ctx.addIssue({
            code: 'custom',
            message: `Line ${invalid + 1} must be a valid IPv4 or IPv6 address or CIDR range.`,
        })
    }
})

export const tokenSchema = z
    .object({
        name: z.string().min(1).max(191),
        fullAccess: z.boolean(),
        scopes: z.record(z.enum(TOKEN_RESOURCES), tokenScopeSchema),
        allowedNetworks: allowedNetworksSchema,
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
                allowed_networks: parseAllowedNetworks(input.allowedNetworks),
            },
        })
    ).data

export const updateTokenNetworks = async (
    id: number,
    allowedNetworks: string
): Promise<ApiKey> =>
    (
        await apiFetch<DataResponse<ApiKey>>(updateRoute(id), {
            body: {
                allowed_networks: parseAllowedNetworks(allowedNetworks),
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
