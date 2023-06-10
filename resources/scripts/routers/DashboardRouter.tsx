import DashboardContainer from '@/components/dashboard/DashboardContainer'
import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'
import TransitionRouter from '@/routers/TransitionRouter'
import { lazy, useContext, useEffect } from 'react'
import { RouteObject } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { routes as serverRoutes } from '@/routers/ServerRouter'
import { lazyLoad } from '@/routers/router'

export const routes: RouteObject[] = [
    { index: true, element: lazyLoad(lazy(() => import('@/routers/DashboardRouter'))) },
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
