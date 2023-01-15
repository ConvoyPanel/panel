import ContentContainer from '@/components/elements/ContentContainer'
import NavigationBar from '@/components/elements/navigation/NavigationBar'
import Spinner from '@/components/elements/Spinner'
import TransitionRouter from '@/routers/TransitionRouter'
import { lazy, Suspense } from 'react'
import { Link, Route, Routes } from 'react-router-dom'
import routes from '@/routers/routes'
import { NotFound } from '@/components/elements/ScreenBlock'
import { NodeContext } from '@/state/admin/node'

const navRoutes = [
    {
        name: 'Overview',
        path: '/admin',
    },
    {
        name: 'Locations',
        path: '/admin/locations',
    },
    {
        name: 'Nodes',
        path: '/admin/nodes',
    },
    {
        name: 'Servers',
        path: '/admin/servers',
    },
    {
        name: 'Users',
        path: '/admin/users',
    },
    {
        name: 'Tokens',
        path: '/admin/tokens',
    },
]

export const AdminBanner = () => (
    <div className='bg-foreground py-1'>
        <ContentContainer>
            <Link to='/'>
                <p className='text-background text-xs font-medium uppercase tracking-wide'>Exit Administration</p>
            </Link>
        </ContentContainer>
    </div>
)

const DashboardRouter = () => {
    return (
        <>
            <AdminBanner />
            <NavigationBar routes={navRoutes} />

            <Routes>
                {routes.admin.dashboard.map(route => (
                    <Route
                        key={route.path}
                        path={route.path}
                        element={
                            <Spinner.Suspense screen={false}>
                                <route.component />
                            </Spinner.Suspense>
                        }
                    />
                ))}

                <Route path={'*'} element={<NotFound full />} />
            </Routes>
        </>
    )
}

export default DashboardRouter
