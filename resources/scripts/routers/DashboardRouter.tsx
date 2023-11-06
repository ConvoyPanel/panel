import { routes as serverRoutes } from '@/routers/ServerRouter'
import TransitionRouter from '@/routers/TransitionRouter'
import { lazyLoad } from '@/routers/helpers'
import { Route } from '@/routers/router'
import { lazy, useContext, useEffect } from 'react'
import { useTranslation } from 'react-i18next'

import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'

import DashboardContainer from '@/components/dashboard/DashboardContainer'


export const routes: Route[] = [
    {
        index: true,
        element: lazyLoad(lazy(() => import('@/routers/DashboardRouter'))),
    },
    ...serverRoutes,
]

const DashboardRouter = () => {
    const { setRoutes } = useContext(NavigationBarContext)
    const { t: tStrings } = useTranslation('strings')

    const routes = [
        {
            name: tStrings('overview'),
            path: '/',
        },
    ]

    useEffect(() => {
        setRoutes(routes)
    }, [])

    return (
        <>
            <TransitionRouter>
                <DashboardContainer />
            </TransitionRouter>
        </>
    )
}

export default DashboardRouter