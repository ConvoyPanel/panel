import { IconLayoutSidebar } from '@tabler/icons-react'
import { useState } from 'react'

import Logo from '@/components/ui/Branding/Logo.tsx'
import { Button } from '@/components/ui/Button'
import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'
import SidebarContent from '@/components/ui/Navigation/Sidebar/SidebarContent.tsx'
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/Sheet'

interface Props {
    nav: SidebarNav
}

const SidebarToggle = ({ nav }: Props) => {
    const [open, setOpen] = useState(false)

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button size='icon' variant='outline' className='sm:hidden'>
                    <IconLayoutSidebar className='h-5 w-5' />
                    <span className='sr-only'>Toggle Menu</span>
                </Button>
            </SheetTrigger>
            <SheetContent side='left' className='w-72 bg-sidebar sm:max-w-xs'>
                <div className='mb-3 flex items-center gap-2 px-1'>
                    <span className='grid h-7 w-7 shrink-0 place-items-center rounded-md bg-primary text-primary-foreground'>
                        <Logo className='h-4 w-4' />
                    </span>
                    <span className='text-sm font-semibold'>Convoy</span>
                </div>
                <nav className='flex flex-col'>
                    <SidebarContent nav={nav} onNavigate={() => setOpen(false)} />
                </nav>
            </SheetContent>
        </Sheet>
    )
}

export default SidebarToggle
