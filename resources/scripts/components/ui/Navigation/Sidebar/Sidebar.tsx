import { cn } from '@/utils'
import {
    IconLayoutSidebarLeftCollapse,
    IconLayoutSidebarLeftExpand,
} from '@tabler/icons-react'

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
    const railHovered = useSidebarStore(state => state.railHovered)
    const setRailHovered = useSidebarStore(state => state.setRailHovered)

    // Hover-expand state lives in the store so it survives the layout remount on
    // navigation (clicking a link while hovering the rail must not snap it shut).
    // Icons keep a fixed left position in both widths, so nothing shifts on
    // collapse; only the labels appear/disappear.
    const expanded = !collapsed || railHovered

    return (
        <aside
            className={cn(
                'hidden h-full shrink-0 sm:block',
                collapsed ? 'w-14' : 'w-64'
            )}
        >
            <div
                onMouseEnter={() => collapsed && setRailHovered(true)}
                onMouseLeave={() => setRailHovered(false)}
                className={cn(
                    'fixed inset-y-0 left-0 z-50 flex flex-col overflow-hidden border-r bg-sidebar text-sidebar-foreground transition-[width] duration-200',
                    expanded ? 'w-64' : 'w-14',
                    collapsed && railHovered ? 'shadow-xl' : null
                )}
            >
                <div className='flex h-14 items-center px-2'>
                    <BrandLink collapsed={!expanded} />
                </div>

                <nav className='no-scrollbar flex flex-1 flex-col overflow-y-auto overflow-x-hidden px-2 pb-3'>
                    <SidebarContent nav={nav} collapsed={!expanded} />
                </nav>

                <div className='border-t px-2 py-3'>
                    <button
                        type='button'
                        onClick={toggleCollapsed}
                        title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                        className='flex h-9 w-full items-center overflow-hidden rounded-md text-sm text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                    >
                        <span className='grid h-9 w-10 shrink-0 place-items-center'>
                            {collapsed ? (
                                <IconLayoutSidebarLeftExpand className='h-[1.15rem] w-[1.15rem]' />
                            ) : (
                                <IconLayoutSidebarLeftCollapse className='h-[1.15rem] w-[1.15rem]' />
                            )}
                        </span>
                        {expanded ? (
                            <span className='min-w-0 flex-1 truncate whitespace-nowrap pr-2 text-left'>
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
