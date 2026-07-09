import { Link, useLocation } from '@tanstack/react-router'

import Logo from '@/components/ui/Branding/Logo.tsx'

const BrandLink = () => {
    const pathname = useLocation({ select: l => l.pathname })
    const isAdmin = pathname.startsWith('/admin')

    return (
        <Link
            to={isAdmin ? '/admin' : '/'}
            className='flex min-w-0 items-center gap-2 rounded-md px-1 py-1'
        >
            <span className='grid h-7 w-7 shrink-0 place-items-center rounded-md bg-primary text-primary-foreground'>
                <Logo className='h-4 w-4' />
            </span>
            <span className='truncate text-sm font-semibold text-sidebar-foreground'>
                Convoy
            </span>
            {isAdmin ? (
                <span className='rounded-full bg-primary/10 px-1.5 py-0.5 text-[0.65rem] font-medium uppercase tracking-wide text-primary'>
                    Admin
                </span>
            ) : null}
        </Link>
    )
}

export default BrandLink
