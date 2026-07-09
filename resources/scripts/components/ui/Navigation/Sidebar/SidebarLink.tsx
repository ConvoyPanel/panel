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
    // A fixed-width leading slot centers the icon in the collapsed rail while
    // keeping it at the exact same x when expanded — so nothing shifts on
    // collapse. Only the label appears/disappears.
    const labelVisibility = collapsed ? 'hidden' : 'flex'

    return (
        <Link
            to={to}
            activeOptions={activeOptions}
            onClick={onNavigate}
            title={collapsed ? label : undefined}
            className={cn(
                'flex h-9 w-full items-center overflow-hidden rounded-md text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
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
            <span className='grid h-9 w-10 shrink-0 place-items-center'>
                <Icon className='h-[1.15rem] w-[1.15rem]' />
            </span>
            <span className={cn('min-w-0 flex-1 items-center gap-2 pr-2', labelVisibility)}>
                <span className='min-w-0 flex-1 truncate'>{label}</span>
                {badge ? (
                    <span className='rounded-full bg-primary/10 px-1.5 py-0.5 text-[0.65rem] font-medium leading-none text-primary'>
                        {badge}
                    </span>
                ) : null}
                {drilldown ? (
                    <IconChevronRight className='h-4 w-4 shrink-0 text-muted-foreground/70' />
                ) : null}
            </span>
        </Link>
    )
}

export default SidebarLink
