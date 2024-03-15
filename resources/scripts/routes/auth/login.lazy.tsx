import { zodResolver } from '@hookform/resolvers/zod'
import { IconBrandOauth } from '@tabler/icons-react'
import { createLazyFileRoute, redirect } from '@tanstack/react-router'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import login from '@/api/auth/login.ts'

import { Button } from '@/components/ui/button.tsx'
import {
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Form, FormButton } from '@/components/ui/form.tsx'
import InputForm from '@/components/ui/forms/InputForm.tsx'


export const Route = createLazyFileRoute('/auth/login')({
    component: Login,
})

const schema = z.object({
    email: z.string().email(),
    password: z.string().min(1, 'Password is required'),
})

function Login() {
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

            redirect('/')
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
                            <IconBrandOauth className='mr-2 h-4 w-4' />
                            OIDC Provider
                        </Button>
                    </CardFooter>
                </form>
            </Form>
        </>
    )
}
