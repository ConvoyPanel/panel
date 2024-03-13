import { useStoreState } from '@/state'
import { Navigate, useLocation } from 'react-router-dom'

interface Props {
    children: JSX.Element
    requireRootAdmin?: boolean
}

const AuthenticatedRoutes: React.FC<Props> = ({
    children,
    requireRootAdmin,
}) => {
    const user = useStoreState(state => state.user.data)
    const location = useLocation()

    if (!user) {
        return <Navigate to='/auth/login' state={{ from: location }} replace />
    }

    if (requireRootAdmin && !user?.rootAdmin) {
        return <Navigate to='/' replace />
    }

    return children
}

export default AuthenticatedRoutes