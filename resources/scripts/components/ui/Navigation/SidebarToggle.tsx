import { IconLayoutSidebar } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import { useState } from 'react'

import { Button } from '@/components/ui/Button'
import Logo from '@/components/ui/Logo.tsx'
import { Route } from '@/components/ui/Navigation/Navigation.types.ts'
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/Sheet'


interface Props {
    routes: Route[]
}

const SidebarToggle = ({ routes }: Props) => {
    const [open, setOpen] = useState(false)

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button size='icon' variant='outline' className='sm:hidden'>
                    <IconLayoutSidebar className='h-5 w-5' />
                    <span className='sr-only'>Toggle Menu</span>
                </Button>
            </SheetTrigger>
            <SheetContent side='left' className='sm:max-w-xs'>
                <nav className='grid gap-6 text-lg font-medium'>
                    <Link
                        href='/'
                        onClick={() => setOpen(false)}
                        className='group flex h-10 w-10 shrink-0 items-center justify-center gap-2 rounded-full bg-primary text-lg font-semibold text-primary-foreground md:text-base'
                    >
                        <Logo className='h-5 w-5 transition-all group-hover:scale-110' />
                        <span className='sr-only'>Convoy Panel</span>
                    </Link>
                    {routes.map(route => (
                        <Link
                            key={route.path}
                            to={route.path}
                            onClick={() => setOpen(false)}
                            className='flex items-center gap-4 px-2.5 hover:text-foreground'
                            inactiveProps={{
                                className: 'text-muted-foreground',
                            }}
                            activeProps={{
                                className: 'text-foreground',
                            }}
                        >
                            <route.icon className='h-5 w-5' />
                            {route.label}
                        </Link>
                    ))}
                </nav>
            </SheetContent>
        </Sheet>
    )
}

export default SidebarToggle
