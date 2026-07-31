import {
    type AuthenticatorQrCode,
    authenticatorQueries,
    confirmAuthenticator,
    enableAuthenticator,
    getQrCode,
    getSecretKey,
} from '@/features/account/authenticator/api.ts'
import { useAuthenticatorModalStore } from '@/features/account/components/AuthenticatorContainer.tsx'
import {
    getRecoveryCodes,
    hasRecoveryCodes,
    recoveryCodeQueries,
} from '@/features/account/recovery-codes/api.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import { getApiErrorMessage, handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

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
import { toast } from '@/components/ui/Toast'

const schema = z.object({
    code: z.string().length(6, 'Enter the 6-digit code from your app'),
})

interface Props {
    /**
     * Hands back the recovery codes this flow minted, or null if the account
     * already had a set. Enabling an authenticator alongside an existing passkey
     * mints nothing — the codes are account-level and shared — so revealing the
     * old set here would present codes the user has already saved as if they
     * were new.
     */
    onEnabled: (codes: string[] | null) => void
}

const AuthenticatorEnableDialog = ({ onEnabled }: Props) => {
    const { open, close } = useModal(useAuthenticatorModalStore, 'enable')
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
        // Whether `enable` was what minted the account's recovery codes. Read
        // before enabling, because that is the only moment the answer is
        // knowable — afterwards the account has codes either way.
        codesAreNew: boolean
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
                const codesExisted = await hasRecoveryCodes()

                await enableAuthenticator()

                const [qrCode, secretKey] = await Promise.all([
                    getQrCode(),
                    getSecretKey(),
                ])

                if (cancelled) {
                    return
                }

                setSetup({ qrCode, secretKey, codesAreNew: !codesExisted })
            } catch (e) {
                if (cancelled) {
                    return
                }

                toast.add({
                    title: getApiErrorMessage(
                        e,
                        'Could not start authenticator setup'
                    ),
                    type: 'error',
                })
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
            // The account may have gained its first recovery codes, which is
            // what decides whether the Recovery codes row renders at all.
            await queryClient.invalidateQueries({
                queryKey: recoveryCodeQueries.all(),
            })

            toast.add({ title: 'Authenticator enabled', type: 'success' })

            // Reveal the codes only if this flow is what created them; an
            // account that already had a passkey already has (and has saved)
            // the same set.
            const codes = setup?.codesAreNew ? await getRecoveryCodes() : null

            close()
            onEnabled(codes)
        },
        onError: e => {
            // A wrong code is a 422 and handleFormErrors surfaces Fortify's own
            // wording. Anything else is NOT a wrong code — reporting it as one
            // sent people rescanning a QR when the real problem was a 500 or an
            // expired session.
            if (handleFormErrors(e, form.setError)) return

            toast.add({
                title: getApiErrorMessage(
                    e,
                    'Could not verify that code. Please try again.'
                ),
                type: 'error',
            })
        },
    })

    // Dismissing before confirming abandons the setup. The secret stays on the
    // account but unconfirmed, so it gates nothing and the next attempt simply
    // mints a fresh one.
    const handleOpenChange = (next: boolean) => {
        if (!next) close()
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
                            {/*
                             * This box owns the QR dimensions in both states, so
                             * the skeleton cannot drift out of sync with the
                             * real code and the dialog never resizes when the
                             * setup material lands. The SVG is stretched to fill
                             * rather than trusted at its intrinsic size — that
                             * size is set server-side by Fortify and is free to
                             * change without this file knowing.
                             */}
                            <div
                                className={'mx-auto size-48 [&_svg]:size-full'}
                            >
                                {isSetupVisible ? (
                                    <div
                                        className={'size-full'}
                                        dangerouslySetInnerHTML={{
                                            __html: setup!.qrCode.svg,
                                        }}
                                    />
                                ) : (
                                    <Skeleton className={'size-full'} />
                                )}
                            </div>

                            {isSetupVisible ? (
                                <>
                                    <p className={'text-center'}>
                                        <strong>Secret Key:</strong>{' '}
                                        {setup!.secretKey}
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
                                // Sized to match what replaces them: the OTP row
                                // is six 32px slots either side of a 16px
                                // separator, and the secret key placeholder sits
                                // inside a real paragraph so it inherits that
                                // line's height instead of dictating it.
                                <>
                                    <p className={'text-center'}>
                                        <Skeleton
                                            className={
                                                'inline-block h-4 w-56 align-middle'
                                            }
                                        />
                                    </p>
                                    <div className={'flex justify-center'}>
                                        <Skeleton className={'h-8 w-52'} />
                                    </div>
                                </>
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
