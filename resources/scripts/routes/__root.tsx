import { Outlet, createRootRoute } from '@tanstack/react-router'
import { Toaster } from 'sonner'

import { ConfirmDialogProvider } from '@/components/ui/AlertDialog'
import { TooltipProvider } from '@/components/ui/Tooltip.tsx'

export const Route = createRootRoute({
    component: RootComponent,
})

function RootComponent() {
    return (
        <TooltipProvider>
            <Toaster richColors />
            <ConfirmDialogProvider />
            <Outlet />
        </TooltipProvider>
    )
}
