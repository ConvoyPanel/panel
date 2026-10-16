import { LazyExoticComponent } from 'react'
import { useNavigate } from 'react-router-dom'
import { BareFetcher, Key, MutatorOptions, mutate } from 'swr'

import { httpErrorToHuman, isNotFoundError } from '@/api/http'

import { ErrorMessage, NotFound } from '@/components/elements/ScreenBlock'
import Spinner from '@/components/elements/Spinner'

export const lazyLoad = (
    LazyElement: LazyExoticComponent<() => JSX.Element>
) => {
    return (
        <Spinner.Suspense>
            <LazyElement />
        </Spinner.Suspense>
    )
}

export const query = async <T,>(
    key: Key,
    fetcher: BareFetcher<T>,
    options: MutatorOptions | false = false
): Promise<T> => {
    const data = await fetcher(key)

    await mutate(key, data, options)

    return data
}

/**
 * What a record page's loader returns when it couldn't load the record, so the
 * page can say so instead of rendering without it.
 */
export interface LoadFailure {
    loadFailed: true
    notFound: boolean
    message: string
}

export const loadFailure = (error: any): LoadFailure => ({
    loadFailed: true,
    notFound: isNotFoundError(error),
    message: httpErrorToHuman(error),
})

export const isLoadFailure = (data: unknown): data is LoadFailure =>
    typeof data === 'object' && data !== null && 'loadFailed' in data

export const LoadFailureScreen = ({
    failure,
    record,
    backTo,
}: {
    failure: LoadFailure
    /** What the page shows, as in "Node not found". */
    record: string
    backTo: string
}) => {
    const navigate = useNavigate()

    return failure.notFound ? (
        <NotFound
            title={`${record} not found`}
            message={`This ${record.toLowerCase()} doesn't exist, or it has been deleted.`}
            onBack={() => navigate(backTo)}
        />
    ) : (
        <ErrorMessage message={failure.message} />
    )
}
