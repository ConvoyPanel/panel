import { zodResolver } from '@hookform/resolvers/zod'
import { IconKey } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import login from '@/api/auth/login.ts'

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


export const Route = createLazyFileRoute('/auth/login')({
    component: Login,
})

const schema = z.object({
    email: z.string().email(),
    password: z.string().min(1, 'Password is required'),
})

function Login() {
    const { redirect } = Route.useSearch()
    const navigate = Route.useNavigate()
    const form = useForm<z.infer<typeof schema>>({
        resolver: zodResolver(schema),
        defaultValues: {
            email: '',
            password: '',
        },
    })

    const onSubmit = async (data: z.infer<typeof schema>) => {
        try {
            await login(data)

            console.log({ redirect })

            await navigate({
                // @ts-expect-error
                to: redirect ? `/${redirect.slice(1)}` : '/',
            })
        } catch {}
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
                <form onSubmit={form.handleSubmit(onSubmit)}>
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
                        <Button className={'w-full'} variant='outline'>
                            <IconKey className='mr-2 h-4 w-4' />
                            Passkeys
                        </Button>
                    </CardFooter>
                </form>
            </Form>
        </>
    )
}
