import { type QueryKey, useQueryClient } from '@tanstack/react-query'

import { Mutator } from '@/types/query.ts'

/**
 * Returns a {@link Mutator} bound to `queryKey`: optimistically patch the cached
 * value via `setQueryData`, then (unless `revalidate === false`) invalidate to
 * refetch. A thin ergonomic wrapper over the TanStack `queryClient` for the
 * common "mutate then update the list" flow in CRUD modals.
 */
const useQueryMutator = <T>(queryKey: QueryKey): Mutator<T> => {
    const queryClient = useQueryClient()

    return async (updater, revalidate = true) => {
        if (updater) queryClient.setQueryData<T>(queryKey, updater)
        if (revalidate || !updater) {
            await queryClient.invalidateQueries({ queryKey })
        }
    }
}

export default useQueryMutator
