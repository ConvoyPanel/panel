import { useFlashKey } from '@/util/useFlash'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { useLocation } from 'react-router-dom'
import { z } from 'zod'

import login from '@/api/auth/login'

import Button from '@/components/elements/Button'
import TextInputForm from '@/components/elements/forms/TextInputForm'

import LoginFormContainer from '@/components/auth/LoginFormContainer'


const LoginContainer = () => {
    const { t: tAuth } = useTranslation('auth')
    const { t: tStrings } = useTranslation('strings')
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('auth:sign_in')
    const location = useLocation()

    useEffect(() => {
        document.title = 'Login | Convoy'
    }, [])

    const schema = z.object({
        email: z.string().email().nonempty(),
        password: z.string().nonempty(),
    })

    const methods = useForm({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues: {
            email: '',
            password: '',
        },
    })

    const submit = async (data: z.infer<typeof schema>) => {
        clearFlashes()

        try {
            await login(data)

            window.location = location.state?.from.pathname || '/'
        } catch (e) {
            console.error(e)

            clearAndAddHttpError(e as Error)
        }
    }

    return (
        <LoginFormContainer
            title='Convoy'
            description={tAuth('sign_in_description')}
            submitting={methods.formState.isSubmitting}
        >
            <FormProvider {...methods}>
                <form onSubmit={methods.handleSubmit(submit)}>
                    <TextInputForm
                        name={'email'}
                        label={tStrings('email')}
                        autoComplete={'email'}
                        className='mt-1 block w-full'
                        autoFocus
                    />
                    <TextInputForm
                        name={'password'}
                        label={tStrings('password')}
                        autoComplete={'password'}
                        type='password'
                        className='mt-1 block w-full'
                    />
                    <div className='flex items-center justify-end mt-6'>
                        <Button type='submit' variant='filled' color='accent'>
                            {tAuth('sign_in')}
                        </Button>
                    </div>
                </form>
            </FormProvider>
        </LoginFormContainer>
    )
}

export default LoginContainer