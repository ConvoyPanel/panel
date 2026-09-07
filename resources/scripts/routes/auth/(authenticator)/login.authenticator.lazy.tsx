import {
    getSecondFactorPasskeyOptions,
    useSecondFactorMethods,
    verifyAuthenticatorChallenge,
    verifySecondFactorPasskey,
} from '@/features/auth/api.ts'
import { isPasskeyDismissal } from '@/features/auth/passkeys.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import {
    WebAuthnAbortService,
    startAuthentication,
} from '@simplewebauthn/browser'
import { IconFingerprint } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useEffect, useRef } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import {
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import {
    Form,
    FormButton,
    FormControl,
    FormField,
    FormItem,
    FormMessage,
} from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSeparator,
    InputOTPSlot,
} from '@/components/ui/InputOTP'
import Spinner from '@/components/ui/Spinner.tsx'
import { toast } from '@/components/ui/Toast'

const otpSchema = z.object({
    type: z.literal('code'),
    code: z.string().length(6, 'Invalid code'),
})

const recoverySchema = z.object({
    type: z.literal('recovery'),
    recoveryCode: z.string().length(21, 'Invalid recovery code'),
})

const passkeySchema = z.object({ type: z.literal('passkey') })

const schema = z.discriminatedUnion('type', [
    otpSchema,
    recoverySchema,
    passkeySchema,
])

// Only two of the three steps have anything left to say. The passkey step used
// to carry a line describing the dialog the browser was about to open; now that
// it opens on arrival, the panel below reports its state and a description
// would just be narrating that a second time.
const DESCRIPTIONS: Record<string, string | null> = {
    code: 'Enter the 6-digit code from your authenticator app.',
    recovery: 'Enter one of your one-time recovery codes.',
    passkey: null,
}

const Authenticator = () => {
    const { redirect } = Route.useSearch()
    const navigate = Route.useNavigate()
    const { data: methods } = useSecondFactorMethods()
    const form = useForm<z.input<typeof schema>>({
        resolver: zodResolver(schema),
        defaultValues: {
            type: 'code',
            code: '',
            recoveryCode: '',
        } as z.input<typeof schema>,
    })

    const type = form.watch('type')
    const code = form.watch('code')

    const finishLogin = async () => {
        await navigate({
            to: redirect ? `/${redirect.slice(1)}` : '/',
        })
    }

    const { mutate: authenticateWithPasskey, isPending: isPasskeyPending } =
        useMutation({
            mutationFn: async () => {
                const optionsJSON = await getSecondFactorPasskeyOptions()
                const response = await startAuthentication({ optionsJSON })

                await verifySecondFactorPasskey(response)
            },
            onSuccess: finishLogin,
            onError: e => {
                // A closed sheet drops straight to whatever else this account
                // has, rather than leaving the user on a screen whose only
                // control re-opens the dialog they just dismissed.
                if (methods?.authenticator) {
                    form.setValue('type', 'code')
                } else if (methods?.recovery) {
                    form.setValue('type', 'recovery')
                }

                if (isPasskeyDismissal(e)) return

                toast.add({ title: 'Failed to verify passkey', type: 'error' })
            },
        })

    const submit = async (_data: any) => {
        const data = _data as z.infer<typeof schema>
        try {
            if (data.type === 'code') {
                await verifyAuthenticatorChallenge({
                    code: data.code,
                })
            } else if (data.type === 'recovery') {
                await verifyAuthenticatorChallenge({
                    recoveryCode: data.recoveryCode,
                })
            } else {
                return
            }

            await finishLogin()
        } catch (e) {
            if (handleFormErrors(e, form.setError)) return

            toast.add({ title: 'Failed to verify code', type: 'error' })

            throw e
        }
    }

    useEffect(() => {
        if (type === 'code' && code.length === 6) {
            form.handleSubmit(submit)()
        }
    }, [code, type])

    // Asking the browser is the whole step, so it happens on arrival. The ref
    // keeps it to one prompt per visit: a sheet that reopens itself after a
    // deliberate cancel is a trap, and React's development double-mount would
    // otherwise abort the first ceremony with a second.
    const prompted = useRef(false)

    useEffect(() => {
        if (!methods || prompted.current) return

        if (!methods.passkey) {
            if (!methods.authenticator) form.setValue('type', 'recovery')

            return
        }

        prompted.current = true
        form.setValue('type', 'passkey')
        authenticateWithPasskey()

        return () => WebAuthnAbortService.cancelCeremony()
        // Primitives, not `methods`: a refetch hands back a new object, and the
        // cleanup above would cancel the ceremony this effect had just opened.
    }, [methods?.passkey, methods?.authenticator])

    const description = DESCRIPTIONS[type]

    return (
        <>
            <CardHeader>
                <CardTitle as={'h1'}>Two-factor required</CardTitle>
                {description && (
                    <CardDescription>{description}</CardDescription>
                )}
            </CardHeader>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <CardContent>
                        {type === 'code' ? (
                            <FormField
                                control={form.control}
                                name={'code'}
                                render={({ field }) => {
                                    const { onChange, ...restField } = field

                                    return (
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
                                                        onChange={e =>
                                                            form.setValue(
                                                                'code',
                                                                e
                                                            )
                                                        }
                                                        {...restField}
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
                                            <FormMessage />
                                        </FormItem>
                                    )
                                }}
                            />
                        ) : type === 'recovery' ? (
                            <InputForm
                                name={'recoveryCode'}
                                label={'Recovery code'}
                            />
                        ) : (
                            <div
                                className={
                                    'bg-muted/50 flex items-center gap-3 rounded-lg p-3'
                                }
                            >
                                {isPasskeyPending ? (
                                    <Spinner className={'size-5 shrink-0'} />
                                ) : (
                                    <IconFingerprint
                                        className={
                                            'text-muted-foreground size-5 shrink-0'
                                        }
                                    />
                                )}
                                <p className={'text-sm'}>
                                    {isPasskeyPending
                                        ? 'Waiting for your passkey.'
                                        : 'Confirm with your passkey to continue.'}
                                </p>
                            </div>
                        )}
                    </CardContent>
                    <CardFooter
                        className={'flex-col justify-end gap-2 sm:flex-row'}
                    >
                        {type !== 'recovery' && methods?.recovery && (
                            <Button
                                type={'button'}
                                className={'max-sm:w-full'}
                                variant={'ghost'}
                                onClick={() =>
                                    form.setValue('type', 'recovery')
                                }
                            >
                                Recovery code
                            </Button>
                        )}
                        {type !== 'code' && methods?.authenticator && (
                            <Button
                                type={'button'}
                                className={'max-sm:w-full'}
                                variant={'ghost'}
                                onClick={() => form.setValue('type', 'code')}
                            >
                                Use a code
                            </Button>
                        )}
                        {type !== 'passkey' && methods?.passkey && (
                            <Button
                                type={'button'}
                                className={'max-sm:w-full'}
                                variant={'ghost'}
                                onClick={() => form.setValue('type', 'passkey')}
                            >
                                Use a passkey
                            </Button>
                        )}
                        {type === 'recovery' && (
                            <FormButton className={'max-sm:w-full'}>
                                Continue
                            </FormButton>
                        )}
                        {type === 'passkey' && (
                            <Button
                                type={'button'}
                                className={'max-sm:w-full'}
                                loading={isPasskeyPending}
                                onClick={() => authenticateWithPasskey()}
                            >
                                Try again
                            </Button>
                        )}
                    </CardFooter>
                </form>
            </Form>
        </>
    )
}

export const Route = createLazyFileRoute(
    '/auth/(authenticator)/login/authenticator'
)({
    component: Authenticator,
})
