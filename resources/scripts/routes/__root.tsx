import { Outlet, createRootRoute } from '@tanstack/react-router'
import { NuqsAdapter } from 'nuqs/adapters/tanstack-router'
import { Toaster } from 'sonner'

import { ConfirmDialogProvider } from '@/components/ui/AlertDialog'
import { TooltipProvider } from '@/components/ui/Tooltip'

export const Route = createRootRoute({
    component: RootComponent,
})

function RootComponent() {
    return (
        <NuqsAdapter>
            <TooltipProvider>
                <Toaster richColors />
                <ConfirmDialogProvider />
                <Outlet />
            </TooltipProvider>
        </NuqsAdapter>
    )
}
