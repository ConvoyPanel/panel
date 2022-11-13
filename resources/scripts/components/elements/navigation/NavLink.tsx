import { NavLink as RouterLink, NavLinkProps } from 'react-router-dom'

const NavLink = ({children, ...props}: Omit<NavLinkProps, 'className'>) => {
    return <RouterLink {...props} className={({ isActive }) => {
        const defaultClasses = 'text-sm transition-colors leading-4 py-4 px-3 hover:text-foreground relative grid place-items-center nav-link'

        return isActive ? `${defaultClasses} nav-link-active` : `${defaultClasses} text-accent-500`
    }} >{children}</RouterLink>
}

export default NavLink
