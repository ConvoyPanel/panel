import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { useQueryClient } from '@tanstack/react-query'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import {
    renamePasskey,
    passkeyQueries,
} from '@/features/account/passkeys/api.ts'

import { usePasskeysModalStore } from '@/features/account/components/PasskeysContainer.tsx'

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
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'

const schema = z.object({
    name: z.string().min(1).max(40),
})

const PasskeyRenameDialog = () => {
    const queryClient = useQueryClient()
    const [passkey, isRenameDialogOpen, closeModal] = usePasskeysModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'rename',
            state.closeModal,
        ])
    )

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: passkey?.name ?? '',
        },
    })

    useEffect(() => {
        form.reset({ name: passkey?.name })
    }, [passkey])

    const submit = async (data: z.infer<typeof schema>) => {
        await renamePasskey(passkey!.id, data.name)

        toast.success('Passkey renamed')

        await queryClient.invalidateQueries({ queryKey: passkeyQueries.all() })

        closeModal('rename')
    }

    return (
        <ResponsiveDialog
            open={isRenameDialogOpen}
            onOpenChange={open => !open && closeModal('rename')}
        >
            <ResponsiveDialogContent className={'max-h-[50vh]'}>
                <ResponsiveDialogHeader className={'overflow-x-hidden'}>
                    <ResponsiveDialogTitle className={'truncate'}>
                        Rename {passkey?.name}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Change the name of this passkey.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>

                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody>
                            <InputForm name={'name'} label={'Name'} />
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Confirm</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default PasskeyRenameDialog
