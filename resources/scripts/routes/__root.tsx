import { QueryClient } from '@tanstack/react-query'
import { Outlet, createRootRouteWithContext } from '@tanstack/react-router'
import { NuqsAdapter } from 'nuqs/adapters/tanstack-router'
import { Toaster } from 'sonner'

import { ConfirmDialogProvider } from '@/components/ui/AlertDialog'
import { TooltipProvider } from '@/components/ui/Tooltip'

export interface RouterContext {
    queryClient: QueryClient
}

export const Route = createRootRouteWithContext<RouterContext>()({
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
