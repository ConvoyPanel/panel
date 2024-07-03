import { IconSettings } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import { useState } from 'react'

import Logo from '@/components/ui/Logo.tsx'
import { Route } from '@/components/ui/Navigation/Navigation.types.ts'
import SidebarLink from '@/components/ui/Navigation/Sidebar/SidebarLink.tsx'


interface Props {
    routes: Route[]
}

const Sidebar = ({ routes }: Props) => {
    const [expanded, setExpanded] = useState(false)

    return (
        <div className={'w-14'}>
            <aside
                data-state={expanded ? 'expanded' : 'collapsed'}
                className='transition-width group fixed inset-y-0 left-0 z-50 hidden w-14 flex-col overflow-y-auto border-r bg-background duration-200 data-[state=expanded]:w-[13rem] data-[state=expanded]:shadow-xl sm:flex'
                onMouseEnter={() => setExpanded(true)}
                onMouseLeave={() => setExpanded(false)}
            >
                <nav className='flex flex-col justify-start gap-2 px-2 sm:py-5'>
                    <div className={'w-full px-0.5'}>
                        <Link
                            to='/'
                            className='relative flex h-9 w-full shrink-0 items-center gap-2 rounded-full bg-primary text-lg font-semibold text-primary-foreground group-data-[state=expanded]:justify-center md:text-base'
                        >
                            <span
                                className={
                                    'absolute left-0 grid h-9 w-9 place-items-center'
                                }
                            >
                                <Logo className='h-4 w-4 transition-all' />
                            </span>
                            <span className='absolute min-w-[11rem] text-center opacity-0 transition-all group-data-[state=expanded]:opacity-100'>
                                Convoy Panel
                            </span>
                        </Link>
                    </div>

                    {routes.map(route => (
                        <SidebarLink
                            to={route.path}
                            icon={route.icon}
                            label={route.label}
                        />
                    ))}
                </nav>
                <nav className='mt-auto flex flex-col items-center gap-4 px-2 sm:py-5'>
                    <SidebarLink
                        to={'/settings'}
                        icon={IconSettings}
                        label={'Settings'}
                    />
                </nav>
            </aside>
        </div>
    )
}

export default Sidebar
