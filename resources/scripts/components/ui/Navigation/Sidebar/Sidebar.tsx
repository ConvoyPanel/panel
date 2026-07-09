import { IconSettings } from '@tabler/icons-react'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'
import BrandLink from '@/components/ui/Navigation/Sidebar/BrandLink.tsx'
import SidebarContent from '@/components/ui/Navigation/Sidebar/SidebarContent.tsx'
import SidebarLink from '@/components/ui/Navigation/Sidebar/SidebarLink.tsx'

interface Props {
    nav: SidebarNav
}

const Sidebar = ({ nav }: Props) => {
    return (
        <aside className='hidden h-full w-64 shrink-0 sm:block'>
            <div className='fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r bg-sidebar text-sidebar-foreground'>
                <div className='flex h-14 items-center px-3'>
                    <BrandLink />
                </div>

                <nav className='no-scrollbar flex flex-1 flex-col overflow-y-auto px-3 pb-3'>
                    <SidebarContent nav={nav} />
                </nav>

                <div className='border-t px-3 py-3'>
                    <SidebarLink
                        to={'/settings'}
                        icon={IconSettings}
                        label={'Settings'}
                    />
                </div>
            </div>
        </aside>
    )
}

export default Sidebar
