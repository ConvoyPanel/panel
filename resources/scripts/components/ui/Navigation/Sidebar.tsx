import { IconPackage, IconSettings } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'

import { Route } from '@/components/ui/Navigation/Navigation.types.ts'
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/Tooltip.tsx'

interface Props {
    routes: Route[]
}

const Sidebar = ({ routes }: Props) => {
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

                {routes.map(route => (
                    <Tooltip key={route.path}>
                        <TooltipTrigger asChild>
                            <Link
                                to={route.path}
                                className='flex h-9 w-9 items-center justify-center rounded-lg transition-colors hover:text-foreground md:h-8 md:w-8'
                                inactiveProps={{
                                    className: 'text-muted-foreground',
                                }}
                                activeProps={{
                                    className:
                                        'bg-accent text-accent-foreground',
                                }}
                            >
                                <route.icon className='h-5 w-5' />
                                <span className='sr-only'>{route.label}</span>
                            </Link>
                        </TooltipTrigger>
                        <TooltipContent side='right'>
                            {route.label}
                        </TooltipContent>
                    </Tooltip>
                ))}
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
