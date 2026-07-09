import { cn } from '@/utils'
import { IconChevronLeft } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import { useEffect, useRef } from 'react'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'
import SidebarLink from '@/components/ui/Navigation/Sidebar/SidebarLink.tsx'
import useSidebarStore from '@/components/ui/Navigation/Sidebar/use-sidebar-store.ts'

interface Props {
    nav: SidebarNav
    collapsed?: boolean
    onNavigate?: () => void
}

// Session-scoped: false until the sidebar has rendered once, so a direct page
// load / hard navigation to a deep URL doesn't play the drill animation. Resets
// naturally on a full page reload (module re-init); survives in-app remounts.
let navHasRendered = false

/**
 * The scrollable body of the sidebar: an optional back affordance + entity
 * context header, then the grouped links. Keyed on `nav.key` so drilling
 * between depths replays the subtle Vercel-style enter animation — from the
 * right going deeper, from the left going back.
 */
const SidebarContent = ({ nav, collapsed, onNavigate }: Props) => {
    const depth = nav.back ? 1 : 0

    // Freeze the drill direction for the lifetime of this nav.key. Read the
    // previous depth NON-reactively (getState) so updating it doesn't re-render
    // and overwrite the animation class with the wrong direction.
    const keyRef = useRef<string | null>(null)
    const goingBackRef = useRef(false)
    const animateRef = useRef(false)
    if (keyRef.current !== nav.key) {
        goingBackRef.current = depth < useSidebarStore.getState().lastDepth
        animateRef.current = navHasRendered
        navHasRendered = true
        keyRef.current = nav.key
    }
    const goingBack = goingBackRef.current
    const animate = animateRef.current

    useEffect(() => {
        useSidebarStore.getState().setLastDepth(depth)
    }, [nav.key, depth])

    // When collapsed (and not hover-expanded), text hides; only icons remain.
    const labelVisibility = collapsed ? 'hidden' : 'block'

    return (
        <div
            key={nav.key}
            className={cn(
                'flex flex-1 flex-col gap-1',
                animate && (goingBack ? 'animate-nav-in-back' : 'animate-nav-in')
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
                        // Fixed-height header area in BOTH states so toggling the
                        // rail never shifts the items vertically: text when
                        // expanded, a Cloudflare-style divider line when collapsed.
                        // Symmetric top/bottom spacing so the collapsed line sits
                        // centered in the gap (accounting for the group's gap-0.5
                        // below and the keyed flex-col's gap-1 above).
                        <div className='mb-2.5 mt-2 flex h-4 items-center'>
                            {collapsed ? (
                                // Fixed-width line aligned to the icon column, so
                                // during the collapse animation it stays the small
                                // collapsed size instead of shrinking from full width.
                                <span className='grid h-4 w-10 shrink-0 place-items-center'>
                                    <span className='h-px w-6 bg-border' />
                                </span>
                            ) : (
                                <span className='truncate pl-3 pr-2 text-[0.7rem] font-medium uppercase leading-none tracking-wide text-muted-foreground/70'>
                                    {group.label}
                                </span>
                            )}
                        </div>
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
