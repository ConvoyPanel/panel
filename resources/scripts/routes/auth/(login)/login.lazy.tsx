import { zodResolver } from '@hookform/resolvers/zod'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import login from '@/api/auth/login.ts'

import LoginWithPasskeyButton from '@/components/interfaces/Auth/LoginWithPasskeyButton.tsx'

import {
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'

export const Route = createLazyFileRoute('/auth/(login)/login')({
    component: Login,
})

const schema = z.object({
    email: z.string().email(),
    password: z.string().min(1, 'Password is required'),
})

function Login() {
    const { redirect } = Route.useSearch()
    const navigate = Route.useNavigate()
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
            <CardHeader className='space-y-2'>
                <CardTitle className='text-3xl'>Sign in</CardTitle>
                <CardDescription>
                    Enter your credentials to continue
                </CardDescription>
            </CardHeader>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <CardContent className='grid gap-4'>
                        <InputForm
                            name={'email'}
                            label={'Email'}
                            type={'email'}
                        />
                        <InputForm
                            name={'password'}
                            label={'Password'}
                            type={'password'}
                        />
                    </CardContent>
                    <CardFooter className={'flex flex-col space-y-4'}>
                        <FormButton className='w-full'>Sign in</FormButton>
                        <div className='relative w-full'>
                            <div className='absolute inset-0 flex items-center'>
                                <span className='w-full border-t' />
                            </div>
                            <div className='relative flex justify-center text-xs uppercase'>
                                <span className='bg-background px-2 text-muted-foreground'>
                                    Or continue with
                                </span>
                            </div>
                        </div>
                        <LoginWithPasskeyButton redirectTo={redirect} />
                    </CardFooter>
                </form>
            </Form>
        </>
    )
}
