import { IconChevronLeft } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'
import SidebarLink from '@/components/ui/Navigation/Sidebar/SidebarLink.tsx'

interface Props {
    nav: SidebarNav
    onNavigate?: () => void
}

/**
 * The scrollable body of the sidebar: an optional back affordance + entity
 * context header, then the grouped links. Keyed on `nav.key` so drilling
 * between depths replays the subtle Vercel-style enter animation.
 */
const SidebarContent = ({ nav, onNavigate }: Props) => {
    return (
        <div key={nav.key} className='animate-nav-in flex flex-1 flex-col gap-1'>
            {nav.back ? (
                <Link
                    to={nav.back.to}
                    onClick={onNavigate}
                    className='mb-1 flex h-9 items-center gap-1.5 rounded-md px-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                >
                    <IconChevronLeft className='h-4 w-4 shrink-0' />
                    <span className='truncate'>{nav.back.label}</span>
                </Link>
            ) : null}

            {nav.context ? (
                <div className='mb-2 flex items-center gap-2.5 px-2 py-1'>
                    {nav.context.icon ? (
                        <span className='grid h-8 w-8 shrink-0 place-items-center rounded-md bg-sidebar-accent text-sidebar-accent-foreground'>
                            <nav.context.icon className='h-[1.15rem] w-[1.15rem]' />
                        </span>
                    ) : null}
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
                </div>
            ) : null}

            {nav.groups.map((group, index) => (
                <div key={group.label ?? `group-${index}`} className='flex flex-col gap-0.5'>
                    {group.label ? (
                        <p className='px-2.5 pb-1 pt-3 text-[0.7rem] font-medium uppercase tracking-wide text-muted-foreground/70'>
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
