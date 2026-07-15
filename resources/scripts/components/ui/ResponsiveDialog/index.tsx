import { cn } from '@/utils'
import type { ComponentProps, ReactElement, ReactNode } from 'react'

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

/** Dialog on desktop, Drawer on mobile. Both Base UI, so props line up. */
const ResponsiveDialog = ({ children, ...props }: ResponsiveDialogProps) => (
    <ResponsiveDialogProvider>
        <ResponsiveDialogRoot {...props}>{children}</ResponsiveDialogRoot>
    </ResponsiveDialogProvider>
)

const ResponsiveDialogRoot = ({
    children,
    onOpenChange,
    ...props
}: ResponsiveDialogProps) => {
    const isDesktop = useIsDesktopDialog()
    const Root = isDesktop ? Dialog : Drawer

    return (
        <Root onOpenChange={open => onOpenChange?.(open)} {...props}>
            {children}
        </Root>
    )
}

const ResponsiveDialogTrigger = (props: SharedProps<ComponentProps<typeof DialogTrigger>>) => {
    const Trigger = useIsDesktopDialog() ? DialogTrigger : DrawerTrigger

    return <Trigger {...props} />
}

const ResponsiveDialogClose = (props: SharedProps<ComponentProps<typeof DialogClose>>) => {
    const Close = useIsDesktopDialog() ? DialogClose : DrawerClose

    return <Close {...props} />
}

const ResponsiveDialogContent = (props: SharedProps<ComponentProps<typeof DialogContent>>) => {
    const Content = useIsDesktopDialog() ? DialogContent : DrawerContent

    return <Content {...props} />
}

const ResponsiveDialogTitle = (props: SharedProps<ComponentProps<typeof DialogTitle>>) => {
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
