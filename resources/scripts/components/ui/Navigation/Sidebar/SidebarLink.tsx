import { cn } from '@/utils'
import { IconChevronRight } from '@tabler/icons-react'
import { Link, LinkOptions } from '@tanstack/react-router'

import { TablerIcon } from '@/lib/tabler.ts'

interface Props {
    to: string
    icon: TablerIcon
    label: string
    activeOptions?: LinkOptions['activeOptions']
    badge?: string
    drilldown?: boolean
    collapsed?: boolean
    className?: string
    onNavigate?: () => void
}

const SidebarLink = ({
    to,
    icon: Icon,
    label,
    activeOptions,
    badge,
    drilldown,
    collapsed,
    className,
    onNavigate,
}: Props) => {
    return (
        <Link
            to={to}
            activeOptions={activeOptions}
            onClick={onNavigate}
            title={collapsed ? label : undefined}
            className={cn(
                'flex h-9 w-full items-center gap-2.5 rounded-md text-sm',
                collapsed ? 'justify-center px-0' : 'px-2.5',
                'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                className
            )}
            inactiveProps={{
                className: 'text-muted-foreground',
            }}
            activeProps={{
                // Vercel-style emphasis: color only — no weight change (which
                // reflows/resizes the text) and no fade transition.
                className: 'bg-sidebar-accent text-sidebar-accent-foreground',
            }}
        >
            <Icon className='h-[1.15rem] w-[1.15rem] shrink-0' />
            {collapsed ? null : (
                <>
                    <span className='min-w-0 flex-1 truncate'>{label}</span>
                    {badge ? (
                        <span className='rounded-full bg-primary/10 px-1.5 py-0.5 text-[0.65rem] font-medium leading-none text-primary'>
                            {badge}
                        </span>
                    ) : null}
                    {drilldown ? (
                        <IconChevronRight className='h-4 w-4 shrink-0 text-muted-foreground/70' />
                    ) : null}
                </>
            )}
        </Link>
    )
}

export default SidebarLink
