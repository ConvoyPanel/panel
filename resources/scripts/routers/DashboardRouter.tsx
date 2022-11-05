import DashboardContainer from '@/components/dashboard/DashboardContainer'
import NavigationBar from '@/components/elements/navigation/NavigationBar'
import Spinner from '@/components/elements/Spinner'
import TransitionRouter from '@/routers/TransitionRouter'
import { Suspense } from 'react'
import { Route, Routes, useLocation } from 'react-router-dom'


const routes = [
  {
    name: 'Overview',
    path: '/',
  },
]

const DashboardRouter = () => {
  const location = useLocation();

  return (
    <>
      <NavigationBar routes={routes} />

      <TransitionRouter>
        <Suspense fallback={<Spinner />}>
          <Routes location={location}>
            <Route path={'/'} element={<DashboardContainer />} />
          </Routes>
        </Suspense>
      </TransitionRouter>
    </>
  )
}

export default DashboardRouter
