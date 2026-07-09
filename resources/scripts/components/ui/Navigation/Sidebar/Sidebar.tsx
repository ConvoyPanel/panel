import { cn } from '@/utils'
import {
    IconLayoutSidebarLeftCollapse,
    IconLayoutSidebarLeftExpand,
} from '@tabler/icons-react'
import { useState } from 'react'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'
import BrandLink from '@/components/ui/Navigation/Sidebar/BrandLink.tsx'
import SidebarContent from '@/components/ui/Navigation/Sidebar/SidebarContent.tsx'
import useSidebarStore from '@/components/ui/Navigation/Sidebar/use-sidebar-store.ts'

interface Props {
    nav: SidebarNav
}

const Sidebar = ({ nav }: Props) => {
    const collapsed = useSidebarStore(state => state.collapsed)
    const toggleCollapsed = useSidebarStore(state => state.toggleCollapsed)
    const [hovered, setHovered] = useState(false)

    // When collapsed, hovering temporarily expands the panel as an overlay
    // without shifting page content (the reserved <aside> stays narrow).
    const showExpanded = !collapsed || hovered

    return (
        <aside
            className={cn(
                'hidden h-full shrink-0 sm:block',
                collapsed ? 'w-14' : 'w-64'
            )}
        >
            <div
                onMouseEnter={() => collapsed && setHovered(true)}
                onMouseLeave={() => setHovered(false)}
                className={cn(
                    'fixed inset-y-0 left-0 z-50 flex flex-col border-r bg-sidebar text-sidebar-foreground transition-[width] duration-200',
                    showExpanded ? 'w-64' : 'w-14',
                    collapsed && hovered ? 'shadow-xl' : null
                )}
            >
                <div
                    className={cn(
                        'flex h-14 items-center',
                        showExpanded ? 'px-3' : 'justify-center px-0'
                    )}
                >
                    <BrandLink collapsed={!showExpanded} />
                </div>

                <nav
                    className={cn(
                        'no-scrollbar flex flex-1 flex-col overflow-y-auto pb-3',
                        showExpanded ? 'px-3' : 'px-2'
                    )}
                >
                    <SidebarContent nav={nav} collapsed={!showExpanded} />
                </nav>

                <div className={cn('border-t py-3', showExpanded ? 'px-3' : 'px-2')}>
                    <button
                        type='button'
                        onClick={toggleCollapsed}
                        title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                        className={cn(
                            'flex h-9 w-full items-center gap-2.5 rounded-md text-sm text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                            showExpanded ? 'px-2.5' : 'justify-center px-0'
                        )}
                    >
                        {collapsed ? (
                            <IconLayoutSidebarLeftExpand className='h-[1.15rem] w-[1.15rem] shrink-0' />
                        ) : (
                            <IconLayoutSidebarLeftCollapse className='h-[1.15rem] w-[1.15rem] shrink-0' />
                        )}
                        {showExpanded ? (
                            <span className='min-w-0 flex-1 truncate text-left'>
                                {collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                            </span>
                        ) : null}
                    </button>
                </div>
            </div>
        </aside>
    )
}

export default Sidebar
