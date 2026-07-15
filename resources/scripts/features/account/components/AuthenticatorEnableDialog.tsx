import {
    authenticatorQueries,
    confirmAuthenticator,
    enableAuthenticator,
    useQrCode,
    useSecretKey,
} from '@/features/account/authenticator/api.ts'
import { useAuthenticatorModalStore } from '@/features/account/components/AuthenticatorContainer.tsx'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import { Button } from '@/components/ui/Button'
import {
    Form,
    FormButton,
    FormControl,
    FormField,
    FormItem,
    FormMessage,
} from '@/components/ui/Form'
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSeparator,
    InputOTPSlot,
} from '@/components/ui/InputOTP'
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
import Skeleton from '@/components/ui/Skeleton.tsx'

const schema = z.object({
    code: z.string().length(6, 'Enter the 6-digit code from your app'),
})

const AuthenticatorEnableDialog = () => {
    const [open, openModal, closeModal] = useAuthenticatorModalStore(
        useShallow(state => [
            state.activeModal === 'enable',
            state.openModal,
            state.closeModal,
        ])
    )
    const queryClient = useQueryClient()
    const [setupReady, setSetupReady] = useState(false)
    const { data: qrCode, isLoading, error } = useQrCode(open && setupReady)
    const { data: secretKey } = useSecretKey(open && setupReady)

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: { code: '' },
    })

    useEffect(() => {
        let cancelled = false

        const main = async () => {
            if (!open) {
                setSetupReady(false)

                return
            }

            setSetupReady(false)
            form.reset({ code: '' })
            await enableAuthenticator()

            if (cancelled) {
                return
            }

            await queryClient.invalidateQueries({
                queryKey: authenticatorQueries.qrCode().queryKey,
            })
            await queryClient.invalidateQueries({
                queryKey: authenticatorQueries.secretKey().queryKey,
            })
            setSetupReady(true)
        }

        void main()

        return () => {
            cancelled = true
        }
    }, [open, queryClient])

    const { mutateAsync: confirm } = useMutation({
        mutationFn: confirmAuthenticator,
        onSuccess: async () => {
            // Enabling only minted the secret; the account is not protected
            // until this lands, which is exactly when the cached status goes
            // stale. Nothing invalidated it before, so dismissing the recovery
            // codes revealed a status still reading `enabled: false` — the
            // Enable screen again, as though the whole flow had done nothing —
            // and only a page refresh told the truth.
            await queryClient.invalidateQueries({
                queryKey: authenticatorQueries.enabled().queryKey,
            })
            await queryClient.invalidateQueries({
                queryKey: authenticatorQueries.recoveryCodes().queryKey,
            })

            toast.success('Authenticator enabled')
            // openModal replaces the active step, so this both closes 'enable'
            // and reveals the codes — no queue involved.
            openModal('recovery-codes')
        },
        onError: e => {
            if (handleFormErrors(e, form.setError)) return

            form.setError('code', {
                type: 'manual',
                message: 'That code is not valid. Try the next one.',
            })
        },
    })

    // Dismissing before confirming abandons the setup. The secret stays on the
    // account but unconfirmed, so it gates nothing and the next attempt simply
    // mints a fresh one.
    const handleOpenChange = (next: boolean) => {
        if (!next) closeModal('enable')
    }

    const isSetupVisible =
        !error && !isLoading && Boolean(qrCode?.url) && Boolean(secretKey)

    return (
        <ResponsiveDialog open={open} onOpenChange={handleOpenChange}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Enable Authenticator
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Scan the QR code with your authenticator app, then enter
                        the code it generates to finish enabling two factor
                        authentication.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>

                <Form {...form}>
                    <form
                        onSubmit={form.handleSubmit(data => confirm(data.code))}
                    >
                        <ResponsiveDialogBody className={'space-y-4'}>
                            {isSetupVisible ? (
                                <>
                                    <div
                                        className={'grid place-items-center'}
                                        dangerouslySetInnerHTML={{
                                            __html: qrCode!.svg,
                                        }}
                                    />
                                    <p className={'text-center'}>
                                        <strong>Secret Key:</strong> {secretKey}
                                    </p>
                                    <FormField
                                        control={form.control}
                                        name={'code'}
                                        render={({ field }) => (
                                            <FormItem>
                                                <div
                                                    className={
                                                        'flex justify-center'
                                                    }
                                                >
                                                    <FormControl>
                                                        <InputOTP
                                                            aria-label={
                                                                'Authenticator code'
                                                            }
                                                            {...field}
                                                            onChange={value =>
                                                                form.setValue(
                                                                    'code',
                                                                    value
                                                                )
                                                            }
                                                            autoFocus
                                                            maxLength={6}
                                                        >
                                                            <InputOTPGroup>
                                                                <InputOTPSlot
                                                                    index={0}
                                                                />
                                                                <InputOTPSlot
                                                                    index={1}
                                                                />
                                                                <InputOTPSlot
                                                                    index={2}
                                                                />
                                                            </InputOTPGroup>
                                                            <InputOTPSeparator />
                                                            <InputOTPGroup>
                                                                <InputOTPSlot
                                                                    index={3}
                                                                />
                                                                <InputOTPSlot
                                                                    index={4}
                                                                />
                                                                <InputOTPSlot
                                                                    index={5}
                                                                />
                                                            </InputOTPGroup>
                                                        </InputOTP>
                                                    </FormControl>
                                                </div>
                                                <FormMessage
                                                    className={'text-center'}
                                                />
                                            </FormItem>
                                        )}
                                    />
                                </>
                            ) : (
                                <Skeleton className={'h-24 w-full'} />
                            )}
                        </ResponsiveDialogBody>

                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton disabled={!isSetupVisible}>
                                Verify
                            </FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AuthenticatorEnableDialog
