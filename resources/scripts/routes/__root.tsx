import { Outlet, createRootRoute } from '@tanstack/react-router'

import { TooltipProvider } from '@/components/ui/Tooltip.tsx'

export const Route = createRootRoute({
    component: () => (
        <TooltipProvider>
            <Outlet />
        </TooltipProvider>
    ),
})
