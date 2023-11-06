import { LazyExoticComponent } from 'react'
import { MutatorOptions } from 'swr'
import { mutate } from 'swr'
import { BareFetcher } from 'swr'
import { Key } from 'swr'

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
