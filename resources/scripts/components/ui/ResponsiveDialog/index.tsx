import { cn } from '@/utils'
import {
    type ComponentProps,
    type MutableRefObject,
    type ReactElement,
    type ReactNode,
    createContext,
    useContext,
    useEffect,
    useRef,
} from 'react'

import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/Dialog'
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from '@/components/ui/Drawer'

import {
    ResponsiveDialogProvider,
    useIsDesktopDialog,
} from './ResponsiveDialogContext'

/**
 * Dialog and Drawer expose slightly different popup *state* to the function form
 * of className (Dialog has `nestedDialogOpen`, Drawer has swipe state), so the
 * shared parts take the plain-string className every consumer here actually
 * uses. Reach for the underlying family directly if you need state-driven
 * styling.
 */
type SharedProps<T> = Omit<T, 'className' | 'render'> & {
    className?: string
    /** Element form only — the state-function form differs between the two families. */
    render?: ReactElement
}

/**
 * The two roots report different `onOpenChange` reasons (a drawer can close by
 * swipe), so the shared root exposes the boolean every consumer here uses. Reach
 * for Dialog/Drawer directly if you need the reason.
 */
interface ResponsiveDialogProps {
    open?: boolean
    defaultOpen?: boolean
    onOpenChange?: (open: boolean) => void
    modal?: boolean
    children?: ReactNode
}

/**
 * How many popovers opened from inside this dialog are currently showing.
 *
 * A popover portals out of the dialog's DOM, so the press that dismisses it reads to the dialog as
 * a press outside itself and closes both at once — pick a server in a combobox, click away to
 * shut the list, and the whole form goes with it. While the count is above zero the dialog ignores
 * outside presses; the innermost layer owns that gesture, which is what a person expects. Escape
 * already behaves, so it is left alone.
 */
const DismissGuardContext = createContext<MutableRefObject<number> | null>(null)

/**
 * Hold the enclosing dialog open while `active` — for anything that layers over it and takes its
 * own outside-press to close. A no-op outside a dialog.
 */
export const useHoldDialogDismiss = (active: boolean) => {
    const guard = useContext(DismissGuardContext)

    useEffect(() => {
        if (!guard || !active) return

        guard.current += 1

        return () => {
            guard.current = Math.max(guard.current - 1, 0)
        }
    }, [guard, active])
}

/** Dialog on desktop, Drawer on mobile. Both Base UI, so props line up. */
const ResponsiveDialog = ({ children, ...props }: ResponsiveDialogProps) => {
    const guard = useRef(0)

    return (
        <DismissGuardContext.Provider value={guard}>
            <ResponsiveDialogProvider>
                <ResponsiveDialogRoot {...props}>
                    {children}
                </ResponsiveDialogRoot>
            </ResponsiveDialogProvider>
        </DismissGuardContext.Provider>
    )
}

const ResponsiveDialogRoot = ({
    children,
    onOpenChange,
    ...props
}: ResponsiveDialogProps) => {
    const isDesktop = useIsDesktopDialog()
    const Root = isDesktop ? Dialog : Drawer
    const guard = useContext(DismissGuardContext)

    return (
        <Root
            onOpenChange={(
                open: boolean,
                details?: { reason?: string; cancel?: () => void }
            ) => {
                if (
                    !open &&
                    details?.reason === 'outside-press' &&
                    (guard?.current ?? 0) > 0
                ) {
                    details.cancel?.()

                    return
                }

                onOpenChange?.(open)
            }}
            {...props}
        >
            {children}
        </Root>
    )
}

const ResponsiveDialogTrigger = (
    props: SharedProps<ComponentProps<typeof DialogTrigger>>
) => {
    const Trigger = useIsDesktopDialog() ? DialogTrigger : DrawerTrigger

    return <Trigger {...props} />
}

const ResponsiveDialogClose = (
    props: SharedProps<ComponentProps<typeof DialogClose>>
) => {
    const Close = useIsDesktopDialog() ? DialogClose : DrawerClose

    return <Close {...props} />
}

const ResponsiveDialogContent = (
    props: SharedProps<ComponentProps<typeof DialogContent>>
) => {
    const Content = useIsDesktopDialog() ? DialogContent : DrawerContent

    return <Content {...props} />
}

const ResponsiveDialogTitle = (
    props: SharedProps<ComponentProps<typeof DialogTitle>>
) => {
    const Title = useIsDesktopDialog() ? DialogTitle : DrawerTitle

    return <Title {...props} />
}

const ResponsiveDialogDescription = (
    props: SharedProps<ComponentProps<typeof DialogDescription>>
) => {
    const Description = useIsDesktopDialog()
        ? DialogDescription
        : DrawerDescription

    return <Description {...props} />
}

interface SlotProps {
    className?: string
    children?: ReactNode
}

// Header and footer pick a side the same way every other part here does. They
// used to inline their own copies of the two families' classes, which meant the
// nova footer bar existed verbatim in both DialogFooter and this file — two
// places to keep in sync for one look. The Dialog/Drawer parts own the values.
const ResponsiveDialogHeader = (props: SlotProps) => {
    const Header = useIsDesktopDialog() ? DialogHeader : DrawerHeader

    return <Header {...props} />
}

const ResponsiveDialogFooter = (props: SlotProps) => {
    const Footer = useIsDesktopDialog() ? DialogFooter : DrawerFooter

    return <Footer {...props} />
}

const ResponsiveDialogBody = ({ className, ...props }: SlotProps) => (
    <div
        data-slot={'responsive-dialog-body'}
        // The drawer popup has no padding of its own, so the body supplies it
        // and needs the bottom inset too — without it the last row sits flush
        // against the footer bar. On desktop the popup's own `p-4` and `gap-4`
        // already do this, hence md:pb-0.
        className={cn('px-4 pb-4 md:px-0 md:pb-0', className)}
        {...props}
    />
)

export {
    ResponsiveDialog,
    ResponsiveDialogTrigger,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogHeader,
    ResponsiveDialogFooter,
    ResponsiveDialogTitle,
    ResponsiveDialogDescription,
    ResponsiveDialogBody,
}
