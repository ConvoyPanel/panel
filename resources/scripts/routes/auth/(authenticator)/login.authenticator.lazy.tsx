import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import verifyAuthenticatorChallenge from '@/api/auth/verifyAuthenticatorChallenge.ts'

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

const schema = z.discriminatedUnion('type', [otpSchema, recoverySchema])

function Authenticator() {
    const { redirect } = Route.useSearch()
    const navigate = Route.useNavigate()
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

    const submit = async (_data: any) => {
        const data = _data as z.infer<typeof schema>
        try {
            if (data.type === 'code') {
                await verifyAuthenticatorChallenge({
                    code: data.code,
                })
            } else {
                await verifyAuthenticatorChallenge({
                    recoveryCode: data.recoveryCode,
                })
            }

            await navigate({
                to: redirect ? `/${redirect.slice(1)}` : '/',
            })
        } catch (e) {
            if (handleFormErrors(e, form.setError)) return

            toast.error('Failed to verify code')

            throw e
        }
    }

    useEffect(() => {
        if (code.length === 6) {
            form.handleSubmit(submit)()
        }
    }, [code])

    return (
        <>
            <CardHeader className={'space-y-2'}>
                <CardTitle className='text-3xl'>2FA Required</CardTitle>
                <CardDescription>
                    Enter the 6-digit code from your authenticator app to verify
                    your identity.
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
                                            <FormControl>
                                                <div
                                                    className={
                                                        'flex justify-center'
                                                    }
                                                >
                                                    <InputOTP
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
                                                </div>
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )
                                }}
                            />
                        ) : (
                            <>
                                <InputForm
                                    name={'recoveryCode'}
                                    label={'Recovery Code'}
                                />
                            </>
                        )}
                    </CardContent>

                    <CardFooter
                        className={
                            'flex flex-col justify-end gap-2 sm:flex-row'
                        }
                    >
                        {type === 'code' ? (
                            <Button
                                className={'max-sm:w-full'}
                                variant={'ghost'}
                                onClick={() =>
                                    form.setValue('type', 'recovery')
                                }
                            >
                                I have a recovery code
                            </Button>
                        ) : (
                            <Button
                                className={'max-sm:w-full'}
                                variant={'ghost'}
                                onClick={() => form.setValue('type', 'code')}
                            >
                                Use OTP instead
                            </Button>
                        )}
                        {type === 'recovery' && (
                            <FormButton className={'max-sm:w-full'}>
                                Continue
                            </FormButton>
                        )}
                    </CardFooter>
                </form>
            </Form>
        </>
    )
}
