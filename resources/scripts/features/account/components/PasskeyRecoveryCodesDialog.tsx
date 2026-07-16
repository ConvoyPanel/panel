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

const PasskeyRecoveryCodesDialog = ({ codes, onClose }: Props) => {
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
                        These one-time codes are the fallback if you lose access
                        to your passkey. Store them somewhere safe.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody>
                    <ul className={'text-center'}>
                        {codes?.map(code => (
                            <li key={code}>{code}</li>
                        ))}
                    </ul>
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

export default PasskeyRecoveryCodesDialog
