import { usePasskeysModalStore } from '@/features/account/components/PasskeysContainer.tsx'
import {
    deletePasskey,
    passkeyQueries,
} from '@/features/account/passkeys/api.ts'
import { recoveryCodeQueries } from '@/features/account/recovery-codes/api.ts'
import { Passkey } from '@/features/account/types.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'

import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'

const PasskeyDeleteDialog = () => {
    const queryClient = useQueryClient()
    const {
        open: isDeleteDialogOpen,
        data: passkey,
        close,
    } = useModal(usePasskeysModalStore, 'delete')

    const [state, submit] = useAsyncFunction(
        async (currentPasskey: Passkey) => {
            try {
                await deletePasskey(currentPasskey.id)

                toast.success('Passkey deleted')

                await queryClient.invalidateQueries({
                    queryKey: passkeyQueries.all(),
                })
                // Deleting the last passkey of an account with no authenticator
                // clears the recovery codes, which retires their row.
                await queryClient.invalidateQueries({
                    queryKey: recoveryCodeQueries.all(),
                })

                close()
            } catch (e) {
                toast.error('Deletion failed')
                throw e
            }
        }
    )

    return (
        <ResponsiveDialog
            open={isDeleteDialogOpen}
            onOpenChange={open => !open && close()}
        >
            <ResponsiveDialogContent className={'max-h-[50vh]'}>
                <ResponsiveDialogHeader className={'overflow-x-hidden'}>
                    <ResponsiveDialogTitle className={'truncate'}>
                        Delete {passkey?.name}?
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to delete this passkey? You will
                        no longer be able to use it to authenticate once you
                        delete it.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>

                <ResponsiveDialogFooter className={'mt-4'}>
                    <ResponsiveDialogTrigger
                        render={<Button variant={'outline'}>Cancel</Button>}
                    />
                    <Button
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={() => passkey && submit(passkey)}
                    >
                        Delete
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default PasskeyDeleteDialog
