import DashboardContainer from '@/components/dashboard/DashboardContainer'
import NavigationBar from '@/components/elements/navigation/NavigationBar'
import { Route, Routes } from 'react-router-dom'

const DashboardRouter = () => {
  return (
    <>
      <NavigationBar />
      <Routes>
        <Route path={'/'} element={<DashboardContainer />} />
      </Routes>
    </>
  )
}

export default DashboardRouter
