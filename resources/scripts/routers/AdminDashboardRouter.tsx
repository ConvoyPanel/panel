import ContentContainer from '@/components/elements/ContentContainer'
import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'
import { useContext, useEffect } from 'react'
import { Link, Outlet, Route, Routes, useMatch, useMatches } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

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
    const { t: tStrings } = useTranslation('strings')

    const navRoutes = [
        {
            name: tStrings('overview'),
            path: '/admin',
            end: true,
        },
        {
            name: tStrings('location', { count: 2 }),
            path: '/admin/locations',
        },
        {
            name: tStrings('node', { count: 2 }),
            path: '/admin/nodes',
        },
        {
            name: tStrings('server', { count: 2 }),
            path: '/admin/servers',
        },
        {
            name: tStrings('user', { count: 2 }),
            path: '/admin/users',
        },
        {
            name: tStrings('token', { count: 2 }),
            path: '/admin/tokens',
        },
    ]

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
