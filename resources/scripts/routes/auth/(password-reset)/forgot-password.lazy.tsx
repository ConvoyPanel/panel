import { requestPasswordReset } from '@/features/auth/password-reset.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { createLazyFileRoute, useNavigate } from '@tanstack/react-router'
import { useState } from 'react'
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
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import { toast } from '@/components/ui/Toast'

const schema = z.object({
    email: z.string().email('Must be an email address'),
})

const ForgotPassword = () => {
    const navigate = useNavigate()
    const [sentTo, setSentTo] = useState<string | null>(null)

    const form = useForm<z.infer<typeof schema>>({
        resolver: zodResolver(schema),
        defaultValues: { email: '' },
    })

    const submit = async ({ email }: z.infer<typeof schema>) => {
        try {
            await requestPasswordReset(email)
            setSentTo(email)
        } catch (error) {
            if (!handleFormErrors(error, form.setError)) {
                toast.add({
                    title: "Couldn't send a reset link. Try again in a minute.",
                    type: 'error',
                })
            }
        }
    }

    const backToSignIn = (
        <Button
            type={'button'}
            variant={'outline'}
            onClick={() => navigate({ to: '/auth/login' })}
        >
            Back to sign in
        </Button>
    )

    // The server gives the same answer for every address, so this screen
    // can't say whether an email was actually sent.
    if (sentTo) {
        return (
            <>
                <CardHeader>
                    <CardTitle as={'h1'}>Check your email</CardTitle>
                    <CardDescription>
                        If {sentTo} has an account, you'll get an email with a
                        reset link.
                    </CardDescription>
                </CardHeader>
                <CardFooter className={'justify-end'}>
                    {backToSignIn}
                </CardFooter>
            </>
        )
    }

    return (
        <>
            <CardHeader>
                <CardTitle as={'h1'}>Reset your password</CardTitle>
            </CardHeader>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <CardContent>
                        <InputForm
                            name={'email'}
                            label={'Email'}
                            type={'email'}
                            placeholder={'you@example.com'}
                            autoComplete={'username'}
                            autoFocus
                        />
                    </CardContent>
                    <CardFooter className={'justify-end gap-2'}>
                        {backToSignIn}
                        <FormButton>Send reset link</FormButton>
                    </CardFooter>
                </form>
            </Form>
        </>
    )
}

export const Route = createLazyFileRoute(
    '/auth/(password-reset)/forgot-password'
)({
    component: ForgotPassword,
})
