import { ModalStore } from '@/hooks/create-modal-store.ts'
import usePasskeyConfirmation from '@/hooks/use-passkey-confirmation.ts'
import useIdentityConfirmationStore, {
    ConfirmationType,
} from '@/stores/identity-confirmation-store.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import { confirmIdentity } from '@/features/auth/identity/api.ts'

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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/Tabs'

const schema = z.object({
    type: z.enum([ConfirmationType.Password, ConfirmationType.Passkey]),
    password: z.string(),
})

interface Props {
    selector?: string
    useModalStore: ModalStore<any, any>
}

export const createAuthMiddleware = <T extends string>(id: T) => ({
    id,
    shouldContinue: () =>
        useIdentityConfirmationStore.getState().isIdentityValid(),
})

const AuthDialog = ({ selector = 'auth', useModalStore }: Props) => {
    const [confirmationType, dispatchIdentityConfirmed] =
        useIdentityConfirmationStore(
            useShallow(state => [state.confirmationType, state.confirmIdentity])
        )
    const { confirm: confirmWithPasskey } = usePasskeyConfirmation()
    const [isAuthDialogOpen, back, closeModal] = useModalStore(
        useShallow(state => [
            state.activeModal === selector,
            state.backOutFromMiddleware,
            state.closeModal,
        ])
    )

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            type: confirmationType,
            password: '',
        },
    })

    useEffect(() => {
        form.reset({ type: confirmationType, password: '' })
    }, [confirmationType])

    const type = form.watch('type')

    // Auto-submit when the modal initially opens with Passkey selected
    useEffect(() => {
        if (isAuthDialogOpen && type === ConfirmationType.Passkey) {
            form.handleSubmit(submit)()
        }
    }, [isAuthDialogOpen])

    // Auto-submit when the user manually selects Passkey
    useEffect(() => {
        if (isAuthDialogOpen && type === ConfirmationType.Passkey) {
            form.handleSubmit(submit)()
        }
    }, [type])

    const submit = async (_data: any) => {
        const data = _data as z.infer<typeof schema>
        try {
            if (data.type === ConfirmationType.Password) {
                await confirmIdentity({ password: data.password })
            } else {
                await confirmWithPasskey()
            }

            dispatchIdentityConfirmed(data.type)

            closeModal(selector)
        } catch (e) {
            if (handleFormErrors(e, form.setError)) return

            const message =
                e instanceof Error ? e.message : 'An unexpected error occurred'

            toast.error(message)

            throw e
        }
    }

    return (
        <ResponsiveDialog
            open={isAuthDialogOpen}
            onOpenChange={open => !open && back(selector)}
        >
            <ResponsiveDialogContent className={'max-h-[50vh]'}>
                <ResponsiveDialogHeader className={'overflow-x-hidden'}>
                    <ResponsiveDialogTitle className={'truncate'}>
                        Authorization Required
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Please enter your credentials to continue
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>

                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody>
                            <Tabs
                                value={type}
                                onValueChange={val =>
                                    form.setValue(
                                        'type',
                                        val as ConfirmationType
                                    )
                                }
                            >
                                <div
                                    className={
                                        'flex justify-center md:justify-start'
                                    }
                                >
                                    <TabsList>
                                        <TabsTrigger
                                            value={ConfirmationType.Password}
                                        >
                                            Password
                                        </TabsTrigger>
                                        <TabsTrigger
                                            value={ConfirmationType.Passkey}
                                        >
                                            Passkey
                                        </TabsTrigger>
                                    </TabsList>
                                </div>
                                <TabsContent value={ConfirmationType.Password}>
                                    <InputForm
                                        name={'password'}
                                        label={'Password'}
                                        type={'password'}
                                    />
                                </TabsContent>
                            </Tabs>
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

export default AuthDialog
