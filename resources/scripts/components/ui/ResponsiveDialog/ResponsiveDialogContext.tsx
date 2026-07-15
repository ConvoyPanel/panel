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
    // `initializeWithValue: false` keeps the first paint deterministic; mantine
    // resolves the real match on mount.
    const isDesktop = useMediaQuery(DESKTOP_QUERY, false)

    return (
        <ResponsiveDialogContext.Provider value={isDesktop}>
            {children}
        </ResponsiveDialogContext.Provider>
    )
}
