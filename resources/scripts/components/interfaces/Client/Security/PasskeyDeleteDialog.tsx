import useAsyncFunction from '@/hooks/use-async-function.ts'
import { Passkey } from '@/types/passkey.ts'
import { toast } from 'sonner'
import { useQueryClient } from '@tanstack/react-query'
import { useShallow } from 'zustand/react/shallow'

import deletePasskey from '@/api/account/passkeys/deletePasskey.ts'
import { getKey as getPasskeysKey } from '@/api/account/passkeys/use-passkeys.ts'

import { usePasskeysModalStore } from '@/components/interfaces/Client/Security/PasskeysContainer.tsx'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaContent,
    CredenzaDescription,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
    CredenzaTrigger,
} from '@/components/ui/Credenza'

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
                    queryKey: getPasskeysKey(),
                })

                closeModal('delete')
            } catch (e) {
                toast.error('Deletion failed')
                throw e
            }
        }
    )

    return (
        <Credenza
            open={isDeleteDialogOpen}
            onOpenChange={open => !open && closeModal('delete')}
        >
            <CredenzaContent className={'max-h-[50vh]'}>
                <CredenzaHeader className={'overflow-x-hidden'}>
                    <CredenzaTitle className={'truncate'}>
                        Delete {passkey?.name}?
                    </CredenzaTitle>
                    <CredenzaDescription>
                        Are you sure you want to delete this passkey? You will
                        no longer be able to use it to authenticate once you
                        delete it.
                    </CredenzaDescription>
                </CredenzaHeader>

                <CredenzaFooter className={'mt-4'}>
                    <CredenzaTrigger asChild>
                        <Button variant={'outline'}>Cancel</Button>
                    </CredenzaTrigger>
                    <Button
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={() => submit(passkey!)}
                    >
                        Delete
                    </Button>
                </CredenzaFooter>
            </CredenzaContent>
        </Credenza>
    )
}

export default PasskeyDeleteDialog
