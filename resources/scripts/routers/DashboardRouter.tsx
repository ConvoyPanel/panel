import DashboardContainer from '@/components/dashboard/DashboardContainer'
import NavigationBar, { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'
import Spinner from '@/components/elements/Spinner'
import TransitionRouter from '@/routers/TransitionRouter'
import { Suspense, useContext, useEffect } from 'react'
import { Route, Routes } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

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
