import {
    authenticatorQueries,
    confirmAuthenticator,
    enableAuthenticator,
    getQrCode,
    getSecretKey,
    type AuthenticatorQrCode,
} from '@/features/account/authenticator/api.ts'
import { useAuthenticatorModalStore } from '@/features/account/components/AuthenticatorContainer.tsx'
import { getApiErrorMessage, handleFormErrors } from '@/utils/http.ts'
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
    // Setup material is fetched straight into state rather than through the
    // query cache. It belongs to ONE setup attempt: `enable` mints a new secret
    // whenever there isn't one, so a cached QR is a QR for a secret the server
    // may already have thrown away. Cached, re-opening setup painted the
    // previous attempt's QR from cache while refetching behind it — scan during
    // that window and your authenticator is seeded with a dead secret, so every
    // code it ever generates is rejected. There is no cache to go stale now.
    const [setup, setSetup] = useState<{
        qrCode: AuthenticatorQrCode
        secretKey: string
    } | null>(null)

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: { code: '' },
    })

    useEffect(() => {
        let cancelled = false

        const main = async () => {
            setSetup(null)

            if (!open) {
                return
            }

            form.reset({ code: '' })

            try {
                await enableAuthenticator()

                const [qrCode, secretKey] = await Promise.all([
                    getQrCode(),
                    getSecretKey(),
                ])

                if (cancelled) {
                    return
                }

                setSetup({ qrCode, secretKey })
            } catch (e) {
                if (cancelled) {
                    return
                }

                toast.error(
                    getApiErrorMessage(e, 'Could not start authenticator setup')
                )
            }
        }

        void main()

        return () => {
            cancelled = true
        }
    }, [open])

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
            // A wrong code is a 422 and handleFormErrors surfaces Fortify's own
            // wording. Anything else is NOT a wrong code — reporting it as one
            // sent people rescanning a QR when the real problem was a 500 or an
            // expired session.
            if (handleFormErrors(e, form.setError)) return

            toast.error(
                getApiErrorMessage(e, 'Could not verify that code. Please try again.')
            )
        },
    })

    // Dismissing before confirming abandons the setup. The secret stays on the
    // account but unconfirmed, so it gates nothing and the next attempt simply
    // mints a fresh one.
    const handleOpenChange = (next: boolean) => {
        if (!next) closeModal('enable')
    }

    const isSetupVisible = setup !== null

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
                                            __html: setup!.qrCode.svg,
                                        }}
                                    />
                                    <p className={'text-center'}>
                                        <strong>Secret Key:</strong> {setup!.secretKey}
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
