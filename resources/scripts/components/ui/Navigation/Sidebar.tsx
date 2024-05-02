import {
    IconChartBar,
    IconHome,
    IconPackage,
    IconSettings,
    IconShoppingCart,
    IconUsers,
} from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'

import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/Tooltip.tsx'


const Sidebar = () => {
    return (
        <aside className='fixed inset-y-0 left-0 z-10 hidden w-14 flex-col border-r bg-background sm:flex'>
            <nav className='flex flex-col items-center gap-4 px-2 sm:py-5'>
                <Link
                    href='#'
                    className='group flex h-9 w-9 shrink-0 items-center justify-center gap-2 rounded-full bg-primary text-lg font-semibold text-primary-foreground md:h-8 md:w-8 md:text-base'
                >
                    <IconPackage className='h-4 w-4 transition-all group-hover:scale-110' />
                    <span className='sr-only'>Acme Inc</span>
                </Link>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Link
                            href='#'
                            className='flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:text-foreground md:h-8 md:w-8'
                        >
                            <IconHome className='h-5 w-5' />
                            <span className='sr-only'>Dashboard</span>
                        </Link>
                    </TooltipTrigger>
                    <TooltipContent side='right'>Dashboard</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Link
                            href='#'
                            className='flex h-9 w-9 items-center justify-center rounded-lg bg-accent text-accent-foreground transition-colors hover:text-foreground md:h-8 md:w-8'
                        >
                            <IconShoppingCart className='h-5 w-5' />
                            <span className='sr-only'>Orders</span>
                        </Link>
                    </TooltipTrigger>
                    <TooltipContent side='right'>Orders</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Link
                            href='#'
                            className='flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:text-foreground md:h-8 md:w-8'
                        >
                            <IconPackage className='h-5 w-5' />
                            <span className='sr-only'>Products</span>
                        </Link>
                    </TooltipTrigger>
                    <TooltipContent side='right'>Products</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Link
                            href='#'
                            className='flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:text-foreground md:h-8 md:w-8'
                        >
                            <IconUsers className='h-5 w-5' />
                            <span className='sr-only'>Customers</span>
                        </Link>
                    </TooltipTrigger>
                    <TooltipContent side='right'>Customers</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Link
                            href='#'
                            className='flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:text-foreground md:h-8 md:w-8'
                        >
                            <IconChartBar className='h-5 w-5' />
                            <span className='sr-only'>Analytics</span>
                        </Link>
                    </TooltipTrigger>
                    <TooltipContent side='right'>Analytics</TooltipContent>
                </Tooltip>
            </nav>
            <nav className='mt-auto flex flex-col items-center gap-4 px-2 sm:py-5'>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Link
                            href='#'
                            className='flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:text-foreground md:h-8 md:w-8'
                        >
                            <IconSettings className='h-5 w-5' />
                            <span className='sr-only'>Settings</span>
                        </Link>
                    </TooltipTrigger>
                    <TooltipContent side='right'>Settings</TooltipContent>
                </Tooltip>
            </nav>
        </aside>
    )
}

export default Sidebar
