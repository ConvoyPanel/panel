import {
    resetLinkQuery,
    resetPassword,
} from '@/features/auth/password-reset.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { PASSWORD_MAX_BYTES, PASSWORD_MIN_LENGTH } from '@/utils/password.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useQuery } from '@tanstack/react-query'
import { createLazyFileRoute, useNavigate } from '@tanstack/react-router'
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
import { FieldGroup } from '@/components/ui/Field'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import PasswordStrengthIndicator from '@/components/ui/Password/PasswordStrengthIndicator.tsx'
import { toast } from '@/components/ui/Toast'

/** Mirrors App\Rules\PasswordPolicy, the same as the invite screen. */
const schema = z.object({
    password: z
        .string()
        .min(
            PASSWORD_MIN_LENGTH,
            `Must be at least ${PASSWORD_MIN_LENGTH} characters`
        )
        .refine(
            value =>
                new TextEncoder().encode(value).length <= PASSWORD_MAX_BYTES,
            `Must be at most ${PASSWORD_MAX_BYTES} bytes`
        ),
})

const ResetPassword = () => {
    const { token } = Route.useParams()
    const { email } = Route.useSearch()
    const navigate = useNavigate()
    const {
        data: valid,
        isError,
        refetch,
    } = useQuery(resetLinkQuery(token, email))

    const form = useForm<z.infer<typeof schema>>({
        resolver: zodResolver(schema),
        defaultValues: { password: '' },
    })

    const password = form.watch('password')

    const submit = async (data: z.infer<typeof schema>) => {
        try {
            await resetPassword(token, email, data.password)

            toast.add({ title: 'Password reset', type: 'success' })

            // Not signed in: the reset proves the mailbox, not a second
            // factor, so they sign in the usual way.
            await navigate({ to: '/auth/login' })
        } catch (error) {
            if (!handleFormErrors(error, form.setError)) {
                // Spent or expired while the form was open. Asking again
                // swaps the form for the screen that explains it.
                await refetch()
            }
        }
    }

    if (isError || !valid) {
        return (
            <>
                <CardHeader>
                    <CardTitle as={'h1'}>This reset link has expired</CardTitle>
                    <CardDescription>
                        It may have been used already. Request a new one.
                    </CardDescription>
                </CardHeader>
                <CardFooter className={'justify-end'}>
                    <Button
                        variant={'outline'}
                        onClick={() =>
                            navigate({ to: '/auth/forgot-password' })
                        }
                    >
                        Request a new link
                    </Button>
                </CardFooter>
            </>
        )
    }

    return (
        <>
            <CardHeader>
                <CardTitle as={'h1'}>Choose a new password</CardTitle>
                <CardDescription>For {email}</CardDescription>
            </CardHeader>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    {/* The address rides along hidden so a password manager
                        files the new password under the right account. */}
                    <input
                        type={'email'}
                        name={'email'}
                        value={email}
                        autoComplete={'username'}
                        readOnly
                        hidden
                    />
                    <CardContent>
                        <FieldGroup>
                            <InputForm
                                name={'password'}
                                label={'New password'}
                                type={'password'}
                                autoComplete={'new-password'}
                                autoFocus
                            />
                            {password !== '' && (
                                <PasswordStrengthIndicator
                                    password={password}
                                />
                            )}
                        </FieldGroup>
                    </CardContent>
                    <CardFooter className={'justify-end'}>
                        <FormButton>Reset password</FormButton>
                    </CardFooter>
                </form>
            </Form>
        </>
    )
}

export const Route = createLazyFileRoute(
    '/auth/(password-reset)/reset-password/$token'
)({
    component: ResetPassword,
})
