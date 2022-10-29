import LoginContainer from '@/components/auth/LoginContainer'
import { Navigate, Route, Routes } from 'react-router-dom'

const AuthenticationRouter = () => {
  return (
    <Routes>
      <Route path={'/login'} element={<LoginContainer />} />
      {/* <Route path={'/*'} element={} /> */}
    </Routes>
  )
}

export default AuthenticationRouter
