import DashboardContainer from '@/components/dashboard/DashboardContainer'
import NavigationBar from '@/components/elements/navigation/NavigationBar'
import Spinner from '@/components/elements/Spinner'
import TransitionRouter from '@/routers/TransitionRouter'
import { Suspense } from 'react'
import { Route, Routes } from 'react-router-dom'


const routes = [
  {
    name: 'Overview',
    path: '/',
  },
]

const DashboardRouter = () => {
  return (
    <>
      <NavigationBar routes={routes} />

      <TransitionRouter>
        <Suspense fallback={<Spinner />}>
          <Routes>
            <Route path={'/'} element={<DashboardContainer />} />
          </Routes>
        </Suspense>
      </TransitionRouter>
    </>
  )
}

export default DashboardRouter
