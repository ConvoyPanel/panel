import { cn } from '@/utils'
import type { ComponentProps, ReactElement, ReactNode } from 'react'

import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/Dialog'
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
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

const ResponsiveDialogHeader = ({ className, ...props }: SlotProps) => {
    const isDesktop = useIsDesktopDialog()

    return (
        <div
            data-slot={'responsive-dialog-header'}
            className={cn(
                'flex flex-col gap-1.5',
                isDesktop ? 'text-left' : 'p-4 text-center',
                className
            )}
            {...props}
        />
    )
}

const ResponsiveDialogFooter = ({ className, ...props }: SlotProps) => {
    const isDesktop = useIsDesktopDialog()

    return (
        <div
            data-slot={'responsive-dialog-footer'}
            className={cn(
                isDesktop
                    ? 'flex flex-col-reverse gap-2 sm:flex-row sm:justify-end'
                    : 'mt-auto flex flex-col gap-2 p-4',
                className
            )}
            {...props}
        />
    )
}

const ResponsiveDialogBody = ({ className, ...props }: SlotProps) => (
    <div
        data-slot={'responsive-dialog-body'}
        className={cn('px-4 md:px-0', className)}
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
