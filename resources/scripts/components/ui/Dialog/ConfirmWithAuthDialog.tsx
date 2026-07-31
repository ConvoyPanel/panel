import useAsyncFunction from '@/hooks/use-async-function.ts'
import { type ReactNode, useRef } from 'react'

import { Button } from '@/components/ui/Button'
import AuthDialog from '@/components/ui/Dialog/AuthDialog.tsx'
import {
    ResponsiveDialog,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'

interface Props<T> {
    /** The row the action targets; null closes the dialog. */
    subject: T | null
    onClose: () => void
    title: ReactNode
    description: (subject: T) => ReactNode
    confirmText: string
    /** Reports its own failure — this only keeps the dialog open when it throws. */
    onConfirm: (subject: T) => Promise<unknown>
}

/**
 * Destructive confirmation for an action behind RequireIdentityConfirmation.
 *
 * The shared confirm() alert cannot carry the gate: <AuthDialog> has to mount
 * inside the dialog it guards for Base UI to nest them, and that alert is a
 * global Radix singleton. So anything gated gets its own dialog, exactly as the
 * create dialogs do — confirming through the alert instead sent the request on a
 * session whose five-minute window had lapsed, and the middleware answered 403
 * with nothing but a failure toast to show for it.
 *
 * The gate is mounted for as long as this dialog is open, so a window that
 * lapses while the user reads the confirmation raises it on its own rather than
 * waiting for the request to fail.
 */
const ConfirmWithAuthDialog = <T,>({
    subject,
    onClose,
    title,
    description,
    confirmText,
    onConfirm,
}: Props<T>) => {
    // The popup plays an exit transition after `open` flips false and still has
    // to render its contents throughout, so the row outlives the null that
    // closed it. Same reason `useModal` keeps `modalData` past close.
    const retained = useRef<T | null>(null)
    if (subject !== null) retained.current = subject
    const row = retained.current

    const [state, confirm] = useAsyncFunction(async () => {
        if (!row) return

        await onConfirm(row)
        onClose()
    })

    return (
        <ResponsiveDialog
            open={subject !== null}
            onOpenChange={open => !open && onClose()}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>{title}</ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        {row && description(row)}
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogFooter className={'mt-4'}>
                    <ResponsiveDialogClose
                        render={<Button variant={'outline'}>Cancel</Button>}
                    />
                    <Button
                        loading={state.loading}
                        variant={'destructive'}
                        // useAsyncFunction re-throws for callers that await it;
                        // nothing awaits a click, so swallow it here — the
                        // failure is already surfaced by onConfirm.
                        onClick={() => void confirm().catch(() => {})}
                    >
                        {confirmText}
                    </Button>
                </ResponsiveDialogFooter>

                {/* Nested inside the content so Base UI gives it no backdrop of
                    its own and this dialog stays visible underneath. */}
                <AuthDialog onCancel={onClose} />
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default ConfirmWithAuthDialog
