import Spinner from '@/components/elements/Spinner'
import { LazyExoticComponent } from 'react'

export const lazyLoad = (LazyElement: LazyExoticComponent<() => JSX.Element>) => {
    return (
        <Spinner.Suspense>
            <LazyElement />
        </Spinner.Suspense>
    )
}