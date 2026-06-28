import axios from '@/lib/axios'

type Method = 'get' | 'post' | 'put' | 'delete' | 'patch' | 'head' | 'options'

export type Route<TMethod extends Method = Method> = {
    url: string
    method: TMethod
}

export type PaginatedResponse<T> = {
    items: T[]
    pagination: App.Data.PaginationMeta
}

export type DataResponse<T> = { data: T }

export async function apiFetch<TResponse>(
    route: Route,
    init: { body?: unknown; params?: Record<string, unknown> } = {}
): Promise<TResponse> {
    const { data } = await axios.request<TResponse>({
        url: route.url,
        method: route.method,
        data: init.body,
        params: init.params,
    })
    return data
}
