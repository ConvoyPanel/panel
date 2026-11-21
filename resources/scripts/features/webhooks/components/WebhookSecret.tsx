import useClipboard from '@/hooks/use-clipboard.ts'
import { IconCheck, IconCopy } from '@tabler/icons-react'
import { useState } from 'react'

import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'

export const SECRET_DESCRIPTION =
    'Copy this secret now. It won’t be shown again. Use it to check the Convoy-Signature header on each delivery.'

/** A one-time secret with a copy button, laid out like the token create dialog's. */
export const SecretValue = ({ secret }: { secret: string }) => {
    const { copy, copied } = useClipboard()

    return (
        <div
            className={'bg-muted flex items-center gap-2 rounded-md border p-3'}
        >
            <code
                className={
                    'grow overflow-x-auto font-mono text-sm whitespace-nowrap'
                }
            >
                {secret}
            </code>
            <Button
                variant={'ghost'}
                size={'icon'}
                aria-label={copied ? 'Secret copied' : 'Copy secret'}
                onClick={() => copy(secret)}
                className={'shrink-0'}
            >
                {copied ? (
                    <IconCheck className={'h-4 w-4'} />
                ) : (
                    <IconCopy className={'h-4 w-4'} />
                )}
            </Button>
        </div>
    )
}

interface Props {
    /** The new secret, or null when the dialog is closed. */
    secret: string | null
    onClose: () => void
}

/** Shows the secret a rotation just issued. */
const WebhookSecretDialog = ({ secret, onClose }: Props) => {
    // Held past close so the secret stays put through the exit animation.
    const [shown, setShown] = useState(secret)
    if (secret !== null && secret !== shown) setShown(secret)

    return (
        <ResponsiveDialog
            open={secret !== null}
            onOpenChange={open => !open && onClose()}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Secret rotated
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        {SECRET_DESCRIPTION}
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody>
                    {shown && <SecretValue secret={shown} />}
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose render={<Button>Done</Button>} />
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default WebhookSecretDialog
