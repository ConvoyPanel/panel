import {
    confirmIdentity,
    identityQueries,
    useIdentityUnconfirmed,
} from '@/features/auth/identity/api.ts'
import usePasskeyConfirmation from '@/hooks/use-passkey-confirmation.ts'
import useIdentityConfirmationStore, {
    ConfirmationType,
} from '@/stores/identity-confirmation-store.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconFingerprint } from '@tabler/icons-react'
import { useQueryClient } from '@tanstack/react-query'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/Tabs'
import { toast } from '@/components/ui/Toast'

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
 * and visible (offset and scaled back) underneath, and confirming just closes
 * this one.
 *
 * The predecessor swapped the two dialogs through a modal queue — unmounting the
 * parent, waiting 250ms, then mounting the gate — which is what made the backdrop
 * flash.
 */
const AuthDialog = ({ onCancel }: Props) => {
    const [confirmationType, setConfirmationType] =
        useIdentityConfirmationStore(
            useShallow(state => [
                state.confirmationType,
                state.setConfirmationType,
            ])
        )
    const { confirm: confirmWithPasskey } = usePasskeyConfirmation()
    const queryClient = useQueryClient()
    // The gate belongs open precisely while identity is unconfirmed; confirming
    // flips this false and reveals the parent underneath. No imperative close.
    //
    // The server owns this fact, so this is only true once it has actually said
    // so — never merely because the answer has not arrived yet, which would
    // flash the gate open on every mount.
    const needsConfirmation = useIdentityUnconfirmed()

    // Base UI only plays the enter transition on a false -> true change of
    // `open`: useTransitionStatus seeds `mounted` from `open`, so a popup that
    // is already open on its first render never enters the 'starting' status and
    // therefore never gets `data-starting-style`. This gate mounts inside the
    // dialog it guards, by which point identity is already unconfirmed, so
    // passing `needsConfirmation` straight through made it appear instantly with
    // no animation while the parent behind it animated. Mount closed and open on
    // the next tick so there is a real transition to play.
    const [isAuthDialogOpen, setAuthDialogOpen] = useState(false)

    useEffect(() => {
        setAuthDialogOpen(needsConfirmation)
    }, [needsConfirmation])

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

    // A passkey needs no input, so the ceremony starts on its own — whether the
    // gate opened with Passkey already selected or the user just picked the tab.
    //
    // This MUST stay a single effect. As two (one keyed on open, one on type),
    // both initial invocations ran together, firing the ceremony twice and
    // 403ing the second on a consumed challenge. The gate opens a tick after it
    // mounts, so `isAuthDialogOpen` transitions false -> true here and the early
    // return below is what keeps that first pass from firing a ceremony.
    useEffect(() => {
        if (!isAuthDialogOpen || type !== ConfirmationType.Passkey) return

        form.handleSubmit(submit)()
    }, [isAuthDialogOpen, type])

    // Cancel/Escape/outside-press. `needsConfirmation` is still true here —
    // dismissing the gate does not confirm anything — so closing this dialog has
    // to be driven explicitly. Without it the gate stayed open and was torn down
    // mid-flight when onCancel() unmounted the parent, so the parent visibly
    // dissolved underneath a still-solid gate. Both now play their exit together.
    const handleOpenChange = (open: boolean) => {
        if (open) return

        setAuthDialogOpen(false)
        onCancel()
    }

    const submit = async (_data: any) => {
        const data = _data as z.infer<typeof schema>
        try {
            const status =
                data.type === ConfirmationType.Password
                    ? await confirmIdentity({ password: data.password })
                    : await confirmWithPasskey()

            // The confirm response *is* the new status, so seed it rather than
            // refetching just to learn what we were already told.
            queryClient.setQueryData(identityQueries.all(), status)
            setConfirmationType(data.type)
        } catch (e) {
            if (handleFormErrors(e, form.setError)) return

            const message =
                e instanceof Error ? e.message : 'An unexpected error occurred'

            toast.add({ title: message, type: 'error' })

            throw e
        }
    }

    return (
        <ResponsiveDialog
            open={isAuthDialogOpen}
            onOpenChange={handleOpenChange}
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
                                {/* A passkey needs no input, but the tab still
                                    has to say what is happening: the ceremony
                                    fires on its own, and a dismissed or failed
                                    prompt otherwise leaves an empty panel with
                                    no hint that Confirm retries it. */}
                                <TabsContent value={ConfirmationType.Passkey}>
                                    <div
                                        className={
                                            'bg-muted/50 flex items-center gap-3 rounded-lg p-3'
                                        }
                                    >
                                        <IconFingerprint
                                            className={
                                                'text-muted-foreground size-5 shrink-0'
                                            }
                                        />
                                        <p
                                            className={
                                                'text-muted-foreground text-sm'
                                            }
                                        >
                                            {form.formState.isSubmitting
                                                ? 'Waiting for your passkey…'
                                                : 'Your browser will ask for your fingerprint, face, or security key. Select Confirm to try again.'}
                                        </p>
                                    </div>
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
