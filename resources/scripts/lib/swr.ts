import { type QueryKey, useQuery } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

export type Key = string | readonly unknown[] | null | undefined | false

export type Fetcher<T> = (...args: any[]) => Promise<T>

type MutateOptions = {
    revalidate?: boolean
    optimisticData?: unknown
    rollbackOnError?: boolean | ((err: unknown) => boolean)
    populateCache?: boolean
}

export type KeyedMutator<T> = (
    data?: T | ((prev?: T) => T | Promise<T | undefined> | undefined),
    revalidateOrOpts?: boolean | MutateOptions
) => Promise<T | undefined>

export type SWRConfiguration<T = unknown> = {
    refreshInterval?: number
    refreshWhenHidden?: boolean
    refreshWhenOffline?: boolean
    revalidateOnFocus?: boolean
    revalidateIfStale?: boolean
    revalidateOnMount?: boolean
    revalidateOnReconnect?: boolean
    dedupingInterval?: number
    focusThrottleInterval?: number
    errorRetryInterval?: number
    errorRetryCount?: number
    fallbackData?: T
    keepPreviousData?: boolean
    onSuccess?: (data: T) => void
    onError?: (error: unknown) => void
    shouldRetryOnError?: boolean
}

const toQueryKey = (key: Key): QueryKey => {
    if (Array.isArray(key)) return key as QueryKey
    if (key === null || key === undefined || key === false) return ['__disabled__']
    return [key as string]
}

const normalizeRevalidate = (opt?: boolean | MutateOptions): boolean => {
    if (typeof opt === 'boolean') return opt
    if (opt && typeof opt === 'object') return opt.revalidate !== false
    return true
}

export default function useSWR<T>(
    key: Key,
    fetcher: Fetcher<T>,
    config: SWRConfiguration<T> = {}
) {
    const queryKey = toQueryKey(key)
    const enabled = key !== null && key !== undefined && key !== false

    const result = useQuery<T>({
        queryKey,
        queryFn: () => fetcher(...(Array.isArray(key) ? key : [key])),
        enabled,
        refetchInterval: config.refreshInterval,
        refetchOnWindowFocus: config.revalidateOnFocus,
        refetchOnReconnect: config.revalidateOnReconnect,
        retry: config.shouldRetryOnError === false ? false : (config.errorRetryCount ?? 3),
        retryDelay: config.errorRetryInterval,
        placeholderData: config.fallbackData as any,
    })

    const mutate: KeyedMutator<T> = async (data, revalidateOrOpts) => {
        if (typeof data === 'function') {
            const updater = data as (prev?: T) => T | Promise<T | undefined> | undefined
            const prev = queryClient.getQueryData<T>(queryKey)
            const next = await updater(prev)
            if (next !== undefined) {
                queryClient.setQueryData<T>(queryKey, next)
            }
        } else if (data !== undefined) {
            queryClient.setQueryData<T>(queryKey, data)
        }

        if (normalizeRevalidate(revalidateOrOpts)) {
            await queryClient.invalidateQueries({ queryKey })
        }

        return queryClient.getQueryData<T>(queryKey)
    }

    return {
        data: result.data,
        error: result.error,
        isLoading: result.isPending && enabled,
        isValidating: result.isFetching,
        mutate,
    }
}

export const preload = async <T>(_key: Key, fetcher: Fetcher<T>) => {
    try {
        await fetcher()
    } catch {
        // swallow — preload is best-effort
    }
}

export async function mutate<T = unknown>(
    key: Key,
    data?: T | Promise<T> | ((prev?: T) => T | Promise<T | undefined> | undefined),
    revalidateOrOpts?: boolean | MutateOptions
) {
    const queryKey = toQueryKey(key)

    if (data !== undefined) {
        if (typeof data === 'function') {
            const updater = data as (prev?: T) => T | Promise<T | undefined> | undefined
            const prev = queryClient.getQueryData<T>(queryKey)
            const next = await updater(prev)
            if (next !== undefined) {
                queryClient.setQueryData<T>(queryKey, next)
            }
        } else {
            const value = await Promise.resolve(data)
            queryClient.setQueryData<T>(queryKey, value)
        }
    }

    if (normalizeRevalidate(revalidateOrOpts)) {
        await queryClient.invalidateQueries({ queryKey })
    }

    return queryClient.getQueryData<T>(queryKey)
}
