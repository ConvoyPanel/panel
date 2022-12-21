import http, { getPaginationSet, PaginatedResult } from '@/api/http'

export interface User {
    id: number
    name: string
    emailVerifiedAt?: Date
    rootAdmin: boolean
    serversCount: number
}

const rawDataToUser = (data: any) => ({
    id: data.id,
    name: data.name,
    // convert to date object
    emailVerifiedAt: data.email_verified_at ? new Date(data.email_verified_at) : undefined,
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
            'filter[name]': query,
            per_page: perPage,
            ...params,
        },
    })

    return {
        items: data.data.map(rawDataToUser),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getUsers