import { lazyLoad } from '@/routers/helpers'
import { Route } from '@/routers/router'
import { lazy } from 'react'


export const routes: Route[] = [
    {
        path: 'coterms',
        children: [
            {
                index: true,
                element: lazyLoad(
                    lazy(
                        () =>
                            import('@/components/admin/coterms/CotermContainer')
                    )
                ),
            },
        ],
    },
]