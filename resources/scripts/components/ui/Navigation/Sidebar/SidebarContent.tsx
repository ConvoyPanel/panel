import { cn } from '@/utils'
import { IconChevronLeft } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'
import SidebarLink from '@/components/ui/Navigation/Sidebar/SidebarLink.tsx'

interface Props {
    nav: SidebarNav
    collapsed?: boolean
    onNavigate?: () => void
}

/**
 * The scrollable body of the sidebar: an optional back affordance + entity
 * context header, then the grouped links. Keyed on `nav.key` so drilling
 * between depths replays the subtle Vercel-style enter animation.
 */
const SidebarContent = ({ nav, collapsed, onNavigate }: Props) => {
    return (
        <div key={nav.key} className='animate-nav-in flex flex-1 flex-col gap-1'>
            {nav.back ? (
                <Link
                    to={nav.back.to}
                    onClick={onNavigate}
                    title={collapsed ? nav.back.label : undefined}
                    className={cn(
                        'mb-1 flex h-9 items-center gap-1.5 rounded-md text-sm text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                        collapsed ? 'justify-center px-0' : 'px-2'
                    )}
                >
                    <IconChevronLeft className='h-4 w-4 shrink-0' />
                    {collapsed ? null : (
                        <span className='truncate'>{nav.back.label}</span>
                    )}
                </Link>
            ) : null}

            {nav.context ? (
                <div
                    className={cn(
                        'mb-2 flex items-center gap-2.5 py-1',
                        collapsed ? 'justify-center px-0' : 'px-2'
                    )}
                >
                    {nav.context.icon ? (
                        <span className='grid h-8 w-8 shrink-0 place-items-center rounded-md bg-sidebar-accent text-sidebar-accent-foreground'>
                            <nav.context.icon className='h-[1.15rem] w-[1.15rem]' />
                        </span>
                    ) : null}
                    {collapsed ? null : (
                        <div className='min-w-0'>
                            <p className='truncate text-sm font-semibold text-sidebar-foreground'>
                                {nav.context.title}
                            </p>
                            {nav.context.subtitle ? (
                                <p className='truncate text-xs text-muted-foreground'>
                                    {nav.context.subtitle}
                                </p>
                            ) : null}
                        </div>
                    )}
                </div>
            ) : null}

            {nav.groups.map((group, index) => (
                <div
                    key={group.label ?? `group-${index}`}
                    className='flex flex-col gap-0.5'
                >
                    {group.label && !collapsed ? (
                        <p className='px-2.5 pb-1 pt-3 text-[0.7rem] font-medium uppercase tracking-wide text-muted-foreground/70'>
                            {group.label}
                        </p>
                    ) : null}
                    {group.label && collapsed && index > 0 ? (
                        <div className='mx-2 my-1.5 border-t' />
                    ) : null}
                    {group.items.map(item => (
                        <SidebarLink
                            key={item.path}
                            to={item.path}
                            icon={item.icon}
                            label={item.label}
                            badge={item.badge}
                            drilldown={item.drilldown}
                            collapsed={collapsed}
                            activeOptions={item.activeOptions}
                            onNavigate={onNavigate}
                        />
                    ))}
                </div>
            ))}
        </div>
    )
}

export default SidebarContent
