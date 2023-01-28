import DashboardContainer from '@/components/dashboard/DashboardContainer'
import NavigationBar, { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'
import Spinner from '@/components/elements/Spinner'
import TransitionRouter from '@/routers/TransitionRouter'
import { Suspense, useContext, useEffect } from 'react'
import { Route, Routes } from 'react-router-dom'

const routes = [
    {
        name: 'Overview',
        path: '/',
    },
]

const DashboardRouter = () => {
    const { setRoutes } = useContext(NavigationBarContext)

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
