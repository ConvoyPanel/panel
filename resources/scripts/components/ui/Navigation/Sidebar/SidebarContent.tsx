import { cn } from '@/utils'
import { IconChevronLeft } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import { useEffect, useLayoutEffect, useRef, useState } from 'react'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'
import SidebarLink from '@/components/ui/Navigation/Sidebar/SidebarLink.tsx'
import useSidebarStore from '@/components/ui/Navigation/Sidebar/use-sidebar-store.ts'

interface Props {
    nav: SidebarNav
    collapsed?: boolean
    onNavigate?: () => void
}

interface ExitingNav {
    nav: SidebarNav
    goingBack: boolean
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
    const [exitingNav, setExitingNav] = useState<ExitingNav | null>(null)

    // Freeze the drill direction for the lifetime of this nav.key. Read the
    // previous depth NON-reactively (getState) so updating it doesn't re-render
    // and overwrite the animation class with the wrong direction.
    const keyRef = useRef<string | null>(null)
    const goingBackRef = useRef(false)
    const animateRef = useRef(false)
    const previousNavRef = useRef<SidebarNav | null>(null)
    const exitTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null)
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

    useLayoutEffect(() => {
        const previousNav = previousNavRef.current

        if (previousNav && previousNav.key !== nav.key && animate) {
            if (exitTimeoutRef.current) {
                clearTimeout(exitTimeoutRef.current)
            }

            setExitingNav({ nav: previousNav, goingBack })
            exitTimeoutRef.current = setTimeout(() => {
                setExitingNav(null)
                exitTimeoutRef.current = null
            }, 300)
        }

        previousNavRef.current = nav
    }, [animate, goingBack, nav])

    useEffect(
        () => () => {
            if (exitTimeoutRef.current) {
                clearTimeout(exitTimeoutRef.current)
            }
        },
        []
    )

    // When collapsed (and not hover-expanded), text hides; only icons remain.
    const labelVisibility = collapsed ? 'hidden' : 'block'

    const content = (contentNav: SidebarNav) => (
        <>
            {contentNav.back ? (
                <Link
                    to={contentNav.back.to}
                    onClick={onNavigate}
                    title={collapsed ? contentNav.back.label : undefined}
                    className='text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground mb-1 flex h-9 items-center overflow-hidden rounded-md text-sm'
                >
                    <span className='grid h-9 w-10 shrink-0 place-items-center'>
                        <IconChevronLeft className='h-[1.15rem] w-[1.15rem]' />
                    </span>
                    <span className={cn('truncate pr-2', labelVisibility)}>
                        {contentNav.back.label}
                    </span>
                </Link>
            ) : null}

            {contentNav.context ? (
                <div className='mb-2 flex items-center overflow-hidden py-1'>
                    <span className='grid h-9 w-10 shrink-0 place-items-center'>
                        {contentNav.context.icon ? (
                            <contentNav.context.icon className='text-sidebar-foreground h-[1.15rem] w-[1.15rem]' />
                        ) : null}
                    </span>
                    <div className={cn('min-w-0 pr-2', labelVisibility)}>
                        <p className='text-sidebar-foreground truncate text-sm font-semibold'>
                            {contentNav.context.title}
                        </p>
                        {contentNav.context.subtitle ? (
                            <p className='text-muted-foreground truncate text-xs'>
                                {contentNav.context.subtitle}
                            </p>
                        ) : null}
                    </div>
                </div>
            ) : null}

            {contentNav.groups.map((group, index) => (
                <div
                    key={group.label ?? `group-${index}`}
                    className='flex flex-col gap-0.5'
                >
                    {group.label ? (
                        // Fixed-height header area in BOTH states so toggling the
                        // rail never shifts the items vertically. Spacing hugs the
                        // label to its group below (conventional header). The
                        // collapsed divider wants to sit centered in the gap
                        // instead, so it's nudged up with a transform — visual
                        // only, so it never affects layout / shifts items.
                        <div className='mt-3 flex h-4 items-center'>
                            {collapsed ? (
                                // Fixed-width line aligned to the icon column, so
                                // during the collapse animation it stays the small
                                // collapsed size instead of shrinking from full width.
                                <span className='grid h-4 w-10 shrink-0 -translate-y-[7px] place-items-center'>
                                    <span className='bg-border h-px w-6' />
                                </span>
                            ) : (
                                <span className='text-muted-foreground/70 truncate pr-2 pl-3 text-[0.7rem] leading-none font-medium tracking-wide uppercase'>
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
        </>
    )

    return (
        <div className='relative flex flex-1 flex-col'>
            {exitingNav ? (
                <div
                    aria-hidden='true'
                    className={cn(
                        'pointer-events-none absolute inset-x-0 top-0 z-10 flex flex-col gap-1',
                        exitingNav.goingBack
                            ? 'animate-nav-out-back'
                            : 'animate-nav-out'
                    )}
                >
                    {content(exitingNav.nav)}
                </div>
            ) : null}

            <div
                key={nav.key}
                className={cn(
                    'flex flex-1 flex-col gap-1',
                    animate &&
                        (goingBack ? 'animate-nav-in-back' : 'animate-nav-in')
                )}
            >
                {content(nav)}
            </div>
        </div>
    )
}

export default SidebarContent
