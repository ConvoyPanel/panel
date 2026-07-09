import { cn } from '@/utils'
import { IconChevronLeft } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import { useEffect } from 'react'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'
import SidebarLink from '@/components/ui/Navigation/Sidebar/SidebarLink.tsx'
import useSidebarStore from '@/components/ui/Navigation/Sidebar/use-sidebar-store.ts'

interface Props {
    nav: SidebarNav
    collapsed?: boolean
    onNavigate?: () => void
}

/**
 * The scrollable body of the sidebar: an optional back affordance + entity
 * context header, then the grouped links. Keyed on `nav.key` so drilling
 * between depths replays the subtle Vercel-style enter animation — from the
 * right going deeper, from the left going back.
 */
const SidebarContent = ({ nav, collapsed, onNavigate }: Props) => {
    const lastDepth = useSidebarStore(state => state.lastDepth)
    const setLastDepth = useSidebarStore(state => state.setLastDepth)

    const depth = nav.back ? 1 : 0
    const goingBack = depth < lastDepth

    useEffect(() => {
        setLastDepth(depth)
    }, [depth, setLastDepth])

    // When collapsed (and not hover-expanded), text hides; only icons remain.
    const labelVisibility = collapsed ? 'hidden' : 'block'

    return (
        <div
            key={nav.key}
            className={cn(
                'flex flex-1 flex-col gap-1',
                goingBack ? 'animate-nav-in-back' : 'animate-nav-in'
            )}
        >
            {nav.back ? (
                <Link
                    to={nav.back.to}
                    onClick={onNavigate}
                    title={collapsed ? nav.back.label : undefined}
                    className='mb-1 flex h-9 items-center overflow-hidden rounded-md text-sm text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                >
                    <span className='grid h-9 w-10 shrink-0 place-items-center'>
                        <IconChevronLeft className='h-[1.15rem] w-[1.15rem]' />
                    </span>
                    <span className={cn('truncate pr-2', labelVisibility)}>
                        {nav.back.label}
                    </span>
                </Link>
            ) : null}

            {nav.context ? (
                <div className='mb-2 flex items-center overflow-hidden py-1'>
                    <span className='grid h-9 w-10 shrink-0 place-items-center'>
                        {nav.context.icon ? (
                            <nav.context.icon className='h-[1.15rem] w-[1.15rem] text-sidebar-foreground' />
                        ) : null}
                    </span>
                    <div className={cn('min-w-0 pr-2', labelVisibility)}>
                        <p className='truncate text-sm font-semibold text-sidebar-foreground'>
                            {nav.context.title}
                        </p>
                        {nav.context.subtitle ? (
                            <p className='truncate text-xs text-muted-foreground'>
                                {nav.context.subtitle}
                            </p>
                        ) : null}
                    </div>
                </div>
            ) : null}

            {nav.groups.map((group, index) => (
                <div
                    key={group.label ?? `group-${index}`}
                    className='flex flex-col gap-0.5'
                >
                    {group.label ? (
                        <p
                            className={cn(
                                'truncate pb-1 pl-10 pr-2 pt-3 text-[0.7rem] font-medium uppercase tracking-wide text-muted-foreground/70',
                                labelVisibility
                            )}
                        >
                            {group.label}
                        </p>
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
