import ContentContainer from '@/components/elements/ContentContainer'
import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'
import { useContext, useEffect } from 'react'
import { Link, Outlet, Route, Routes, useMatch, useMatches } from 'react-router-dom'

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
    const { setRoutes } = useContext(NavigationBarContext)
    const isDashboardArea = useMatch('/admin/:id/')
    const isDashboardArea2 = useMatch('/admin')

    useEffect(() => {
        if (Boolean(isDashboardArea) || Boolean(isDashboardArea2)) {
            setRoutes(navRoutes)
        }
    }, [isDashboardArea, isDashboardArea2])

    return (
        <>
            <Outlet />
        </>
    )
}

export default AdminDashboardRouter
