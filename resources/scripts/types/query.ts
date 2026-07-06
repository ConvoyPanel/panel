/**
 * A cache updater bound to a specific query key. Replaces the old SWR
 * `KeyedMutator`: call it with an updater to optimistically patch the cached
 * value, and (unless `revalidate` is false) it refetches afterwards.
 *
 * Build one from a list page with `useQueryMutator(getKey(params))` and pass it
 * down to child mutation components.
 */
export type Mutator<T> = (
    updater?: (prev: T | undefined) => T | undefined,
    revalidate?: boolean
) => Promise<void>
