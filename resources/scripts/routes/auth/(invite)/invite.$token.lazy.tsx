import { acceptInvite, inviteQuery } from '@/features/auth/invites.ts'
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

/**
 * Mirrors App\Rules\PasswordPolicy, the same way the account and admin forms do. The breach
 * check runs server-side because it needs the network, so it arrives as a field error.
 */
const schema = z.object({
    password: z
        .string()
        .min(
            PASSWORD_MIN_LENGTH,
            `Password must be at least ${PASSWORD_MIN_LENGTH} characters`
        )
        .refine(
            value =>
                new TextEncoder().encode(value).length <= PASSWORD_MAX_BYTES,
            `Password must be at most ${PASSWORD_MAX_BYTES} bytes`
        ),
})

const AcceptInvite = () => {
    const { token } = Route.useParams()
    const navigate = useNavigate()
    const { data: invite, isError } = useQuery(inviteQuery(token))

    const form = useForm<z.infer<typeof schema>>({
        resolver: zodResolver(schema),
        defaultValues: { password: '' },
    })

    const password = form.watch('password')

    const submit = async (data: z.infer<typeof schema>) => {
        try {
            await acceptInvite(token, data.password)

            // Redeeming signs them in, so send them to the panel rather than to a login form
            // asking for the password they typed a second ago.
            await navigate({ to: '/' })
        } catch (error) {
            if (!handleFormErrors(error, form.setError)) {
                toast.add({
                    title: 'This invitation is no longer valid',
                    type: 'error',
                })
            }
        }
    }

    /*
     * Unknown, spent and expired all arrive here identically — the server does not distinguish
     * them, so neither can this. Offering "request a new one" rather than dead-ending, because
     * an expired link with no way forward is a support ticket every time.
     */
    if (isError || !invite) {
        return (
            <>
                <CardHeader>
                    <CardTitle>This invitation has expired</CardTitle>
                    <CardDescription>
                        It may have been used already. Ask an administrator for
                        a new one.
                    </CardDescription>
                </CardHeader>
                <CardFooter className={'justify-end'}>
                    <Button
                        variant={'outline'}
                        onClick={() => navigate({ to: '/auth/login' })}
                    >
                        Back to sign in
                    </Button>
                </CardFooter>
            </>
        )
    }

    return (
        <>
            <CardHeader>
                <CardTitle>Welcome, {invite.name}</CardTitle>
                <CardDescription>
                    Choose a password for {invite.email}.
                </CardDescription>
            </CardHeader>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <CardContent>
                        <FieldGroup>
                            <InputForm
                                name={'password'}
                                label={'Password'}
                                type={'password'}
                                autoComplete={'new-password'}
                                autoFocus
                            />
                            {/* Only once there is something to grade: a column of
                                red crosses under an empty box reads as a failure
                                rather than as guidance. */}
                            {password !== '' && (
                                <PasswordStrengthIndicator
                                    password={password}
                                />
                            )}
                        </FieldGroup>
                    </CardContent>
                    <CardFooter className={'justify-end'}>
                        <FormButton>Set password and sign in</FormButton>
                    </CardFooter>
                </form>
            </Form>
        </>
    )
}

export const Route = createLazyFileRoute('/auth/(invite)/invite/$token')({
    component: AcceptInvite,
})
