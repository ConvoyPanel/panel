import { IconSearch } from '@tabler/icons-react'

import { Input } from '@/components/ui/Input'
import AvatarWithDropdown from '@/components/ui/Navigation/AvatarWithDropdown.tsx'
import Breadcrumbs from '@/components/ui/Navigation/Breadcrumbs.tsx'
import { Route } from '@/components/ui/Navigation/Navigation.types.ts'
import SidebarToggle from '@/components/ui/Navigation/SidebarToggle.tsx'


interface Props {
    routes: Route[]
}

const Header = ({ routes }: Props) => {
    return (
        <header className='sticky top-0 z-30 flex h-14 items-center gap-4 border-b bg-background px-4 sm:static sm:h-auto sm:border-0 sm:bg-transparent sm:px-6'>
            <SidebarToggle routes={routes} />
            <Breadcrumbs />
            <div className='relative ml-auto flex-1 md:grow-0'>
                <IconSearch className='absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground' />
                <Input
                    type='search'
                    placeholder='Search...'
                    className='w-full rounded-lg bg-background pl-8 md:w-[200px] lg:w-[336px]'
                />
            </div>
            <AvatarWithDropdown />
        </header>
    )
}

export default Header
