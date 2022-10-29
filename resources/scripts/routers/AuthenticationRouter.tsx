import LoginContainer from '@/components/auth/LoginContainer'
import { Route, Routes } from 'react-router-dom'

const AuthenticationRouter = () => {
  return (
    <Routes>
      <Route path={'/login'} element={<LoginContainer />} />

    </Routes>
  )
}

export default AuthenticationRouter
