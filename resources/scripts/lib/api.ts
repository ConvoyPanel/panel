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

export type ApiFetchInit = {
    body?: unknown
    params?: Record<string, unknown>
    /** Extra request headers, e.g. the offset a resumable upload resumes at. */
    headers?: Record<string, string>
    /** Abort the request — what a Cancel button is wired to. */
    signal?: AbortSignal
    /** Bytes sent so far, for a request large enough to be worth showing. */
    onUploadProgress?: (loaded: number, total?: number) => void
}

export async function apiFetch<TResponse>(
    route: Route,
    init: ApiFetchInit = {}
): Promise<TResponse> {
    const { data } = await axios.request<TResponse>({
        url: route.url,
        method: route.method,
        data: init.body,
        params: init.params,
        headers: init.headers,
        signal: init.signal,
        onUploadProgress: init.onUploadProgress
            ? event => init.onUploadProgress!(event.loaded, event.total)
            : undefined,
    })
    return data
}
