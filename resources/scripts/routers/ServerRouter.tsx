import NavigationBar from '@/components/elements/navigation/NavigationBar'
import ScreenBlock, { NotFound } from '@/components/elements/ScreenBlock'
import routes from '@/routers/routes'
import { Route, Routes } from 'react-router-dom'

const ServerRouter = () => {
  return (
    <>
      <NavigationBar />
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
