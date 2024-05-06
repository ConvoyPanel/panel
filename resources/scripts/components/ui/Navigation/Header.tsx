import { IconLayoutSidebar, IconPackage, IconSearch } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'

import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/Breadcrumb'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import Avatar from '@/components/ui/Navigation/Avatar.tsx'
import { Route } from '@/components/ui/Navigation/Navigation.types.ts'
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/Sheet'

interface Props {
    routes: Route[]
}

const Header = ({ routes }: Props) => {
    return (
        <header className='sticky top-0 z-30 flex h-14 items-center gap-4 border-b bg-background px-4 sm:static sm:h-auto sm:border-0 sm:bg-transparent sm:px-6'>
            <Sheet>
                <SheetTrigger asChild>
                    <Button size='icon' variant='outline' className='sm:hidden'>
                        <IconLayoutSidebar className='h-5 w-5' />
                        <span className='sr-only'>Toggle Menu</span>
                    </Button>
                </SheetTrigger>
                <SheetContent side='left' className='sm:max-w-xs'>
                    <nav className='grid gap-6 text-lg font-medium'>
                        <Link
                            href='#'
                            className='group flex h-10 w-10 shrink-0 items-center justify-center gap-2 rounded-full bg-primary text-lg font-semibold text-primary-foreground md:text-base'
                        >
                            <IconPackage className='h-5 w-5 transition-all group-hover:scale-110' />
                            <span className='sr-only'>Acme Inc</span>
                        </Link>
                        {routes.map(route => (
                            <Link
                                key={route.path}
                                to={route.path}
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
            <Breadcrumb className='hidden md:flex'>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink asChild>
                            <Link href='#'>Dashboard</Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbLink asChild>
                            <Link href='#'>Orders</Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>Recent Orders</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
            <div className='relative ml-auto flex-1 md:grow-0'>
                <IconSearch className='absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground' />
                <Input
                    type='search'
                    placeholder='Search...'
                    className='w-full rounded-lg bg-background pl-8 md:w-[200px] lg:w-[336px]'
                />
            </div>
            <Avatar />
        </header>
    )
}

export default Header
