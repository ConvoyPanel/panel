import { login } from '@/features/auth/api.ts'
import LoginWithPasskeyButton from '@/features/auth/components/LoginWithPasskeyButton.tsx'
import OAuthProviderButtons from '@/features/auth/components/OAuthProviderButtons.tsx'
import { oauthErrorMessage, oauthProviders } from '@/features/auth/oauth.ts'
import {
    PASSKEY_AUTOFILL_AUTOCOMPLETE,
    usePasskeyAutofill,
} from '@/features/auth/passkeys.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconArrowRight } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import { toast } from '@/components/ui/Toast'

const schema = z.object({
    email: z.string().email(),
    password: z.string().min(1, 'Password is required'),
})

const Login = () => {
    const { redirect, oauth_error: oauthError } = Route.useSearch()
    const navigate = Route.useNavigate()

    // Offered from inside the email field, with nothing to press first. The
    // button beside "Sign in" is what is left for the cases this cannot reach.
    usePasskeyAutofill(redirect)

    const providers = oauthProviders()

    useEffect(() => {
        if (oauthError) {
            toast.add({ title: oauthErrorMessage(oauthError), type: 'error' })
            void navigate({
                to: '/auth/login',
                search: { redirect: redirect ? redirect : undefined },
                replace: true,
            })
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [oauthError])

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            email: '',
            password: '',
        },
    })

    const submit = async (data: z.infer<typeof schema>) => {
        try {
            const response = await login(data)

            if (response.twoFactor) {
                await navigate({
                    to: '/auth/login/authenticator',
                    search: {
                        redirect: redirect ? redirect : undefined,
                    },
                })

                return
            }

            await navigate({
                to: redirect ? `/${redirect.slice(1)}` : '/',
            })
        } catch {
            form.setError('email', {
                message: 'Invalid email or password',
            })
            form.setError('password', {
                message: 'Invalid email or password',
            })
        }
    }

    return (
        <>
            <CardHeader>
                <CardTitle as={'h1'} size={'display'}>
                    Sign in
                </CardTitle>
            </CardHeader>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <CardContent className={'grid gap-5'}>
                        {providers.length > 0 && (
                            <div className={'grid gap-5'}>
                                {/* Providers sit above the fields: an account
                                    that signs in through one should not have to
                                    read past a form it can never submit. */}
                                <OAuthProviderButtons redirectTo={redirect} />
                                <div
                                    className={
                                        'text-muted-foreground flex items-center gap-3 text-xs uppercase'
                                    }
                                >
                                    <span
                                        className={'bg-border h-px flex-1'}
                                        aria-hidden
                                    />
                                    or
                                    <span
                                        className={'bg-border h-px flex-1'}
                                        aria-hidden
                                    />
                                </div>
                            </div>
                        )}
                        <InputForm
                            name={'email'}
                            label={'Email'}
                            type={'email'}
                            variant={'underline'}
                            labelTone={'mono'}
                            autoComplete={PASSKEY_AUTOFILL_AUTOCOMPLETE}
                        />
                        <InputForm
                            name={'password'}
                            label={'Password'}
                            type={'password'}
                            variant={'underline'}
                            labelTone={'mono'}
                            autoComplete={'current-password'}
                        />
                        <div
                            className={'flex flex-wrap items-center gap-2 pt-1'}
                        >
                            <FormButton>
                                Sign in
                                <IconArrowRight className={'size-4'} />
                            </FormButton>
                            <LoginWithPasskeyButton redirectTo={redirect} />
                        </div>
                    </CardContent>
                </form>
            </Form>
        </>
    )
}

export const Route = createLazyFileRoute('/auth/(login)/login')({
    component: Login,
})
