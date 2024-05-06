import {
    Outlet,
    createRootRouteWithContext,
    useRouter,
} from '@tanstack/react-router'
import { useEffect } from 'react'

import { TooltipProvider } from '@/components/ui/Tooltip.tsx'

interface RouterContext {
    getTitle: () => string
}

export const Route = createRootRouteWithContext<RouterContext>()({
    component: RootComponent,
})

function RootComponent() {
    const router = useRouter()

    const matchWithTitle = [...router.state.matches]
        .reverse()
        .find(d => d.context.getTitle)

    const title = matchWithTitle?.context.getTitle() || 'Convoy'

    useEffect(() => {
        document.title = title
    }, [title])

    return (
        <TooltipProvider>
            <Outlet />
        </TooltipProvider>
    )
}
