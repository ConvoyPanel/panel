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
    /**
     * Dismissing the gate has to close the flow it guards — otherwise the user is
     * left looking at settings they never authenticated for.
     */
    onCancel: () => void
}

/**
 * Identity gate, rendered INSIDE the dialog it guards so Base UI treats it as a
 * nested dialog: the child gets no backdrop of its own, the parent stays mounted
 * and visible (scaled back) underneath, and confirming just closes this one.
 *
 * The predecessor swapped the two dialogs through a modal queue — unmounting the
 * parent, waiting 250ms, then mounting the gate — which is what made the backdrop
 * flash.
 */
const AuthDialog = ({ onCancel }: Props) => {
    const [confirmationType, dispatchIdentityConfirmed] =
        useIdentityConfirmationStore(
            useShallow(state => [state.confirmationType, state.confirmIdentity])
        )
    const { confirm: confirmWithPasskey } = usePasskeyConfirmation()
    // Open precisely while identity is unconfirmed; confirming flips this false
    // and reveals the parent underneath. No imperative close needed.
    const isAuthDialogOpen = useIdentityConfirmationStore(state =>
        !state.isIdentityValid()
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
            onOpenChange={open => !open && onCancel()}
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
