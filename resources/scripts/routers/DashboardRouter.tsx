import { Route, Routes } from 'react-router-dom'

const DashboardRouter = () => {
    return (
      <Routes>
        <Route path={'/*'} element={<h1>Logged in</h1>} />
      </Routes>
    )
  }

  export default DashboardRouter
