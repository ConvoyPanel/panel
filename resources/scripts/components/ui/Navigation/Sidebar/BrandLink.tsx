import { cn } from '@/utils'
import { Link, useLocation } from '@tanstack/react-router'

import Logo from '@/components/ui/Branding/Logo.tsx'

interface Props {
    collapsed?: boolean
}

const BrandLink = ({ collapsed }: Props) => {
    const pathname = useLocation({ select: l => l.pathname })
    const isAdmin = pathname.startsWith('/admin')

    return (
        <Link
            to={isAdmin ? '/admin' : '/'}
            className='flex h-9 items-center overflow-hidden rounded-md'
        >
            <span className='grid h-9 w-10 shrink-0 place-items-center'>
                <span className='grid h-7 w-7 place-items-center rounded-md bg-primary text-primary-foreground'>
                    <Logo className='h-4 w-4' />
                </span>
            </span>
            <span
                className={cn(
                    'items-center gap-2 pr-2',
                    collapsed ? 'hidden' : 'flex'
                )}
            >
                <span className='truncate text-sm font-semibold text-sidebar-foreground'>
                    Convoy
                </span>
                {isAdmin ? (
                    <span className='rounded-full bg-primary/10 px-1.5 py-0.5 text-[0.65rem] font-medium uppercase tracking-wide text-primary'>
                        Admin
                    </span>
                ) : null}
            </span>
        </Link>
    )
}

export default BrandLink
