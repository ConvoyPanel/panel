import { useMutation } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'
import type { Key } from '@/lib/swr.ts'

type Trigger<Arg, Data> = (arg?: Arg) => Promise<Data>

type Options<Arg, Data> = {
    onSuccess?: (data: Data, key: Key, config: { arg?: Arg }) => void
    onError?: (err: unknown, key: Key, config: { arg?: Arg }) => void
    revalidate?: boolean
    populateCache?: boolean | ((data: Data, prev?: any) => any)
    optimisticData?: unknown
    rollbackOnError?: boolean
    throwOnError?: boolean
}

const toQueryKey = (key: Key) => {
    if (Array.isArray(key)) return key as readonly unknown[]
    if (key === null || key === undefined || key === false) return ['__disabled__']
    return [key as string]
}

export default function useSWRMutation<Data = any, Arg = any>(
    key: Key,
    fetcher: (key: any, opts: { arg: Arg }) => Promise<Data>,
    options: Options<Arg, Data> = {}
) {
    const mutation = useMutation<Data, unknown, Arg | undefined>({
        mutationFn: async (arg) => {
            return fetcher(key, { arg: arg as Arg })
        },
        onSuccess: async (data, arg) => {
            if (options.revalidate !== false) {
                await queryClient.invalidateQueries({ queryKey: toQueryKey(key) })
            }
            options.onSuccess?.(data, key, { arg: arg as Arg })
        },
        onError: (err, arg) => {
            options.onError?.(err, key, { arg: arg as Arg })
        },
    })

    const trigger: Trigger<Arg, Data> = async (arg) => {
        return mutation.mutateAsync(arg)
    }

    return {
        trigger,
        data: mutation.data,
        error: mutation.error,
        isMutating: mutation.isPending,
        reset: mutation.reset,
    }
}
