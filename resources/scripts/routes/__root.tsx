import { Outlet, createRootRoute } from '@tanstack/react-router'

import { ConfirmDialogProvider } from '@/components/ui/AlertDialog'
import { Toaster } from '@/components/ui/Toast'
import { TooltipProvider } from '@/components/ui/Tooltip.tsx'


export const Route = createRootRoute({
    component: RootComponent,
})

function RootComponent() {
    return (
        <TooltipProvider>
            <Toaster />
            <ConfirmDialogProvider />
            <Outlet />
        </TooltipProvider>
    )
}
