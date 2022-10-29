import { Navigate, Route, RouteProps, useLocation } from 'react-router'
import { useStoreState } from '@/state'
import { ReactNode } from 'react'

interface Props {
  children: JSX.Element
}

const AuthenticatedRoutes: React.FC<Props> = ({ children }) => {
  const isAuthenticated = useStoreState((state) => state.user.data?.email)
  const location = useLocation()

  if (!isAuthenticated) {
    return <Navigate to='/auth/login' state={{ from: location }} replace />
  }

  return children
}

export default AuthenticatedRoutes
