import NavigationBar from '@/components/elements/navigation/NavigationBar'
import { NotFound } from '@/components/elements/ScreenBlock'
import routes from '@/routers/routes'
import { matchPath, Route, Routes, useLocation } from 'react-router-dom'

const navRoutes = [
  {
    name: 'Overview',
    path: '/servers/:id',
  },
]

const ServerRouter = () => {
  const location = useLocation()
  const identifier = matchPath('/servers/:id', location.pathname)!.params.id as string

  return (
    <>
      <NavigationBar routes={navRoutes} breadcrumb={identifier} />
      <Routes>
          {routes.server.map((route) => (
            <Route key={route.path} path={route.path} element={<route.component />} />
          ))}

        <Route path={'*'} element={<NotFound full />} />
      </Routes>
    </>
  )
}

export default ServerRouter
