import useAsyncFunction from '@/hooks/use-async-function.ts'
import { Passkey } from '@/features/account/types.ts'
import { toast } from 'sonner'
import { useQueryClient } from '@tanstack/react-query'
import { useShallow } from 'zustand/react/shallow'

import {
    deletePasskey,
    passkeyQueries,
} from '@/features/account/passkeys/api.ts'

import { usePasskeysModalStore } from '@/features/account/components/PasskeysContainer.tsx'

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
    const [passkey, isDeleteDialogOpen, closeModal] = usePasskeysModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'delete',
            state.closeModal,
        ])
    )

    const [state, submit] = useAsyncFunction(
        async (currentPasskey: Passkey) => {
            try {
                await deletePasskey(currentPasskey.id)

                toast.success('Passkey deleted')

                await queryClient.invalidateQueries({
                    queryKey: passkeyQueries.all(),
                })

                closeModal('delete')
            } catch (e) {
                toast.error('Deletion failed')
                throw e
            }
        }
    )

    return (
        <ResponsiveDialog
            open={isDeleteDialogOpen}
            onOpenChange={open => !open && closeModal('delete')}
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
                        render={
                            <Button variant={'outline'}>Cancel</Button>
                        }
                    />
                    <Button
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={() => submit(passkey!)}
                    >
                        Delete
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default PasskeyDeleteDialog
