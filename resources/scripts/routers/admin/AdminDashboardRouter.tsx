import ContentContainer from '@/components/elements/ContentContainer'
import NavigationBar from '@/components/elements/navigation/NavigationBar'
import Spinner from '@/components/elements/Spinner'
import TransitionRouter from '@/routers/TransitionRouter'
import { lazy, Suspense } from 'react'
import { Link, Outlet, Route, Routes } from 'react-router-dom'
import routes from '@/routers/router'
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

const AdminDashboardRouter = () => {
    return (
        <>
            <AdminBanner />
            <NavigationBar routes={navRoutes} />

            <Outlet />
        </>
    )
}

export default AdminDashboardRouter
