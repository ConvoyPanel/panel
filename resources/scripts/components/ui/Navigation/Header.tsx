import Avatar from '@/components/ui/Navigation/Avatar.tsx'
import Breadcrumbs from '@/components/ui/Navigation/Breadcrumbs.tsx'
import NavSearch from '@/components/ui/Navigation/NavSearch.tsx'
import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'
import SidebarToggle from '@/components/ui/Navigation/SidebarToggle.tsx'

interface Props {
    nav: SidebarNav
}

const Header = ({ nav }: Props) => {
    // shrink-0: as a flex child of AppLayout's column, this would otherwise give
    // up a pixel or two of its h-14 whenever the content below it cannot shrink
    // — leaving a sliver between it and any sticky bar pinned to top-14, which
    // the page then visibly scrolls through.
    return (
        <header className='bg-background sticky top-0 z-30 flex h-14 shrink-0 items-center gap-4 border-b px-4 sm:static sm:h-auto sm:border-0 sm:bg-transparent sm:px-6'>
            <SidebarToggle nav={nav} />
            <Breadcrumbs />
            <div className='ml-auto flex items-center gap-2'>
                <NavSearch nav={nav} />
                <Avatar />
            </div>
        </header>
    )
}

export default Header
