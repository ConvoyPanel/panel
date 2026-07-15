import { useMediaQuery } from '@mantine/hooks'
import { createContext, useContext, type ReactNode } from 'react'

/**
 * A responsive dialog is a Dialog on desktop and a Drawer on mobile — the same
 * pattern shadcn documents. Both families are Base UI, so every part below is a
 * straight alias with identical props; there is no adapter here.
 *
 * The breakpoint is resolved ONCE at the root and shared. The predecessor
 * (ResponsiveDialog) called useMediaQuery in all eight of its parts, which meant eight
 * independent subscriptions to one breakpoint that could re-render separately.
 */
export const DESKTOP_QUERY = '(min-width: 768px)'

const ResponsiveDialogContext = createContext<boolean | null>(null)

export const useIsDesktopDialog = () => {
    const value = useContext(ResponsiveDialogContext)

    if (value === null) {
        throw new Error(
            'ResponsiveDialog parts must be rendered inside <ResponsiveDialog>.'
        )
    }

    return value
}

export const ResponsiveDialogProvider = ({
    children,
}: {
    children: ReactNode
}) => {
    // Resolve the match synchronously on the first render. Mantine defaults to
    // `getInitialValueInEffect: true`, which returns the `false` initial value
    // until a mount effect runs — so on desktop every dialog rendered as a
    // Drawer for one paint and then swapped to a Dialog. Drawer and Dialog are
    // different component types, so that swap remounts the whole subtree: it
    // replayed the enter transitions and re-created the child's Base UI store,
    // which is what made a nested gate intermittently miss its parent's
    // DialogRootContext and render its own backdrop over the parent.
    const isDesktop = useMediaQuery(DESKTOP_QUERY, false, {
        getInitialValueInEffect: false,
    })

    return (
        <ResponsiveDialogContext.Provider value={isDesktop}>
            {children}
        </ResponsiveDialogContext.Provider>
    )
}
