import { ReactNode, useMemo } from 'react'

import Header from '@/components/ui/Navigation/Header.tsx'
import {
    SidebarNavInput,
    normalizeNav,
} from '@/components/ui/Navigation/Navigation.types.ts'
import Sidebar from '@/components/ui/Navigation/Sidebar/Sidebar.tsx'

interface Props {
    /** A grouped `SidebarNav`, or a flat `Route[]` for simple screens. */
    routes: SidebarNavInput
    children?: ReactNode
}

const AppLayout = ({ routes, children }: Props) => {
    const nav = useMemo(() => normalizeNav(routes), [routes])

    return (
        <div className='flex min-h-screen w-full min-w-0 bg-muted/40'>
            <Sidebar nav={nav} />
            <div className='flex min-w-0 grow flex-col overflow-x-hidden sm:gap-4 sm:py-4'>
                <Header nav={nav} />
                <main className={'h-full min-w-0 @container'}>
                    <div
                        className={
                            'flex h-full min-w-0 flex-col gap-2 p-4 @md:gap-4 sm:px-6 sm:py-0'
                        }
                    >
                        {children}
                    </div>
                </main>
            </div>
        </div>
    )
}

export default AppLayout
