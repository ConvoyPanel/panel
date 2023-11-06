import { User, rawDataToUser } from '@/api/admin/users/getUsers'
import http, { PaginatedResult, getPaginationSet } from '@/api/http'

export type ApiTokenType = 'application' | 'account'

export interface Token {
    id: number
    user: User
    type: ApiTokenType
    name: string
    lastUsedAt: Date | null
    plainTextToken?: string
}

export const rawDataToToken = (data: any): Token => ({
    id: data.id,
    user: rawDataToUser(data.user.data),
    type: data.type,
    name: data.name,
    lastUsedAt: data.last_used_at ? new Date(data.last_used_at) : null,
    plainTextToken: data.plain_text_token,
})

export interface QueryParams {
    query?: string
    page?: number
    perPage?: number
}

export type TokenResponse = PaginatedResult<Token>

const getTokens = async ({
    query,
    perPage = 50,
    ...params
}: QueryParams): Promise<TokenResponse> => {
    const { data } = await http.get('/api/admin/tokens', {
        params: {
            'filter[*]': query,
            'per_page': perPage,
            ...params,
        },
    })

    return {
        items: data.data.map(rawDataToToken),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getTokens