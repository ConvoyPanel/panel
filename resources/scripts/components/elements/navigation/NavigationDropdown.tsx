import { ReactNode, useEffect } from 'react'
import { Link, useMatch, useNavigate } from 'react-router-dom'

import ContentContainer from '@/components/elements/ContentContainer'

interface LinkProps {
    children: ReactNode
    to: string
}
const NavLink = ({ children, to }: LinkProps) => {
    return (
        <Link
            className='flex items-center h-12 border-b border-accent-200 bg-transparent active:bg-accent-100 transition-colors'
            to={to}
        >
            <span>{children}</span>
        </Link>
    )
}

interface Props {
    logout: () => void
    visible?: boolean
}

const NavigationDropdown = ({ logout, visible }: Props) => {
    const navigate = useNavigate()
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
                            <NavLink to='/admin'>Admin Center</NavLink>
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
