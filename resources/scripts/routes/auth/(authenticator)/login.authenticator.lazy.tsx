import {
    getSecondFactorPasskeyOptions,
    useSecondFactorMethods,
    verifyAuthenticatorChallenge,
    verifySecondFactorPasskey,
} from '@/features/auth/api.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { startAuthentication } from '@simplewebauthn/browser'
import { IconFingerprint } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useEffect } from 'react'
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
import { toast } from '@/components/ui/Toast'

export const Route = createLazyFileRoute(
    '/auth/(authenticator)/login/authenticator'
)({
    component: Authenticator,
})

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

function Authenticator() {
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
            onError: () =>
                toast.add({ title: 'Failed to verify passkey', type: 'error' }),
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

    useEffect(() => {
        if (!methods || methods.authenticator) return

        form.setValue('type', methods.passkey ? 'passkey' : 'recovery')
    }, [methods])

    return (
        <>
            <CardHeader className={'space-y-2'}>
                <CardTitle as='h1' className='text-3xl'>
                    Second factor required
                </CardTitle>
                <CardDescription>
                    {type === 'code' &&
                        'Enter the 6-digit code from your authenticator app.'}
                    {type === 'recovery' &&
                        'Enter one of your one-time recovery codes.'}
                    {type === 'passkey' &&
                        'Verify your identity with a passkey.'}
                </CardDescription>
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
                            <>
                                <InputForm
                                    name={'recoveryCode'}
                                    label={'Recovery Code'}
                                />
                            </>
                        ) : (
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
                                <p className={'text-muted-foreground text-sm'}>
                                    Your browser will ask for your fingerprint,
                                    face, screen lock, or security key.
                                </p>
                            </div>
                        )}
                    </CardContent>

                    <CardFooter
                        className={
                            'flex flex-col justify-end gap-2 sm:flex-row'
                        }
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
                                I have a recovery code
                            </Button>
                        )}
                        {type !== 'code' && methods?.authenticator && (
                            <Button
                                type={'button'}
                                className={'max-sm:w-full'}
                                variant={'ghost'}
                                onClick={() => form.setValue('type', 'code')}
                            >
                                Use authenticator instead
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
                                Verify with passkey
                            </Button>
                        )}
                    </CardFooter>
                </form>
            </Form>
        </>
    )
}
