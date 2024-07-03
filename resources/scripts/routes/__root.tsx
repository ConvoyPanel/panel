import { Outlet, createRootRoute, useRouterState } from '@tanstack/react-router'
import { useEffect } from 'react'

import { TooltipProvider } from '@/components/ui/Tooltip.tsx'


export const Route = createRootRoute({
    component: RootComponent,
})

function RootComponent() {
    const routeMeta = useRouterState({
        select: state => {
            return state.matches.map(match => match.meta!).filter(Boolean)
        },
    })

    useEffect(() => {
        const title =
            routeMeta.length > 0
                ? routeMeta[routeMeta.length - 1].find(tag => tag.title)?.title
                : null

        document.title = title ? `${title} | Convoy` : 'Convoy'
    }, [routeMeta])

    return (
        <TooltipProvider>
            <Outlet />
        </TooltipProvider>
    )
}
