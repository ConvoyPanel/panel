import { useStoreState } from '@/state'
import { ReactNode, useEffect } from 'react'
import { Link, useMatch, useNavigate } from 'react-router-dom'

import ContentContainer from '@/components/elements/ContentContainer'

interface LinkProps {
    children: ReactNode
    onClick: () => void
    to: string
}
const NavLink = ({ children, onClick, to }: LinkProps) => {
    return (
        <Link
            className='flex items-center h-12 border-b border-accent-200 bg-transparent active:bg-accent-100 transition-colors'
            to={to}
            onClick={onClick}
        >
            <span>{children}</span>
        </Link>
    )
}

interface Props {
    logout: () => void
    onClose: () => void
    visible?: boolean
}

const NavigationDropdown = ({ logout, onClose, visible }: Props) => {
    const user = useStoreState(state => state.user.data)
    const isAdminArea = useMatch('/admin/*')

    useEffect(() => {
        if (visible) {
            document.body.classList.add('fixed', 'w-full')
        } else {
            document.body.classList.remove('fixed', 'w-full')
        }
    }, [visible])

    return (
        <>
            {visible && (
                <div
                    className={`inset-x-0 ${
                        isAdminArea ? 'top-[80px]' : 'top-[56px]'
                    } pt-1.5 bottom-0 block fixed bg-background z-[3000] overflow-y-scroll`}
                >
                    <ContentContainer>
                        <div className='flex flex-col w-full'>
                            {user?.rootAdmin ? (
                                <NavLink to='/admin' onClick={onClose}>
                                    Admin Center
                                </NavLink>
                            ) : null}
                            <button
                                className='flex items-center h-12 border-b border-accent-200 bg-transparent active:bg-accent-100 transition-colors'
                                onClick={logout}
                            >
                                <span>Sign Out</span>
                            </button>
                        </div>
                    </ContentContainer>
                </div>
            )}
        </>
    )
}

export default NavigationDropdown
