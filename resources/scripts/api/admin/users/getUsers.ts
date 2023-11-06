import http, { PaginatedResult, getPaginationSet } from '@/api/http'

export interface User {
    id: number
    name: string
    email: string
    emailVerifiedAt: Date | null
    rootAdmin: boolean
    serversCount: number
}

export const rawDataToUser = (data: any): User => ({
    id: data.id,
    name: data.name,
    email: data.email,
    emailVerifiedAt: data.email_verified_at
        ? new Date(data.email_verified_at)
        : null,
    rootAdmin: data.root_admin,
    serversCount: data.servers_count,
})

export interface QueryParams {
    query?: string
    page?: number
    perPage?: number
}

export type UserResponse = PaginatedResult<User>

const getUsers = async ({
    query,
    perPage = 50,
    ...params
}: QueryParams): Promise<UserResponse> => {
    const { data } = await http.get('/api/admin/users', {
        params: {
            'filter[*]': query,
            'per_page': perPage,
            ...params,
        },
    })

    return {
        items: data.data.map(rawDataToUser),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getUsers