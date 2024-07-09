import { cn } from '@/utils'
import { Link } from '@tanstack/react-router'

import { TablerIcon } from '@/lib/tabler.ts'

interface Props {
    to: string
    icon: TablerIcon
    label: string
    className?: string
}

const SidebarLink = ({ to, icon: Icon, label, className }: Props) => {
    return (
        <Link
            to={to}
            className={cn(
                'relative flex h-10 w-full items-center rounded-lg transition-colors hover:bg-accent hover:text-foreground',
                className
            )}
            inactiveProps={{
                className: 'text-muted-foreground',
            }}
            activeProps={{
                className: 'bg-accent text-accent-foreground',
            }}
        >
            <span className={'absolute grid h-10 w-10 place-items-center'}>
                <Icon className='left-0 h-5 w-5 transition-colors' />
            </span>
            <span
                className={cn(
                    'absolute left-7 min-w-fit opacity-0 transition-all',
                    'group-data-[state=expanded]:left-[2.75rem] group-data-[state=expanded]:opacity-100',
                    'group-data-[keep-expanded=true]:left-[2.75rem] group-data-[keep-expanded=true]:opacity-100'
                )}
            >
                <span className={'w-full truncate'}>{label}</span>
            </span>
        </Link>
    )
}

export default SidebarLink
