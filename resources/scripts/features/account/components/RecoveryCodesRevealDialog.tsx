import RecoveryCodeList from '@/features/account/components/RecoveryCodeList.tsx'
import useClipboard from '@/hooks/use-clipboard.ts'

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

interface Props {
    codes: string[] | null
    onClose: () => void
}

/**
 * The one-time "save these" step, shared by both enable flows (first passkey,
 * first authenticator).
 *
 * The copy is deliberately factor-agnostic. One set of codes backs every second
 * factor on the account, so telling a passkey user these are "if you lose your
 * passkey" and a TOTP user these are "if you lose your authenticator app"
 * described one set as two — and left the two dialogs contradicting each other
 * about what resetting them would throw away.
 *
 * Codes are passed in rather than fetched: this is a reveal of what was just
 * minted, not a read of durable state. Managing them afterwards lives in
 * RecoveryCodesMainDialog.
 */
const RecoveryCodesRevealDialog = ({ codes, onClose }: Props) => {
    const { copy } = useClipboard({
        successMessage: 'Copied recovery codes to clipboard',
    })

    return (
        <ResponsiveDialog
            open={codes !== null}
            onOpenChange={open => !open && onClose()}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Save your recovery codes
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Each of these one-time codes signs you in if you lose
                        access to your other methods. Store them somewhere safe
                        — this is the only time they are shown.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody>
                    {codes && <RecoveryCodeList codes={codes} />}
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter>
                    <Button
                        variant={'outline'}
                        onClick={() => codes && copy(codes.join('\n'))}
                    >
                        Copy codes
                    </Button>
                    <ResponsiveDialogClose
                        render={<Button>I saved my codes</Button>}
                    />
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default RecoveryCodesRevealDialog
