import { cn } from '@/utils'
import { Link, useLocation } from '@tanstack/react-router'

import Logo from '@/components/ui/Branding/Logo.tsx'

const BrandLink = () => {
    const pathname = useLocation({ select: l => l.pathname })
    const isAdmin = pathname.startsWith('/admin')

    return (
        <Link to={isAdmin ? '/admin' : '/'} className='mx-1'>
            <span
                className={cn(
                    'grid h-8 w-8 place-items-center rounded-full text-primary-foreground',
                    isAdmin ? 'bg-orange-600 dark:bg-orange-500' : 'bg-primary'
                )}
            >
                <Logo className='h-4 w-4' />
            </span>
            <span className='sr-only'>Convoy Panel</span>
        </Link>
    )
}

export default BrandLink
