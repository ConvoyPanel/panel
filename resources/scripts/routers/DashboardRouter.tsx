import DashboardContainer from '@/components/dashboard/DashboardContainer'
import { Route, Routes } from 'react-router-dom'

const DashboardRouter = () => {
    return (
      <Routes>
        <Route path={'/*'} element={<DashboardContainer />} />
      </Routes>
    )
  }

  export default DashboardRouter
