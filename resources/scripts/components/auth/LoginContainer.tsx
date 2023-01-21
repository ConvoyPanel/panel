import login from '@/api/auth/login'
import LoginFormContainer from '@/components/auth/LoginFormContainer'
import { useFormik } from 'formik'
import * as yup from 'yup'
import useFlash from '@/util/useFlash'
import { useEffect } from 'react'
import TextInput from '@/components/elements/inputs/TextInput'
import Button from '@/components/elements/Button'
import { useLocation, useNavigate } from 'react-router-dom'

const LoginContainer = () => {
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const navigate = useNavigate()
    const location = useLocation()

    useEffect(() => {
        document.title = 'Login | Convoy'
    }, [])

    const rules = yup.object({
        email: yup.string().email('Enter a valid email').required('Email is required'),
        password: yup.string().required('Password is required'),
    })

    const form = useFormik({
        initialValues: {
            email: '',
            password: '',
        },
        validationSchema: rules,
        onSubmit: values => {
            clearFlashes()

            login(values)
                .then(() => {
                    window.location = location.state?.from.pathname || '/'
                })
                .catch(error => {
                    console.error(error)

                    form.setSubmitting(false)
                    clearAndAddHttpError({ error })
                })
        },
    })

    return (
        <form onSubmit={form.handleSubmit}>
            <LoginFormContainer title='Convoy' description='Sign in to your account' submitting={form.isSubmitting}>
                <TextInput
                    label='Email'
                    name='email'
                    className='mt-1 block w-full'
                    value={form.values.email}
                    onChange={form.handleChange}
                    error={form.touched.email ? form.errors.email : undefined}
                    autoFocus
                    required
                />
                <TextInput
                    label='Password'
                    name='password'
                    value={form.values.password}
                    onChange={form.handleChange}
                    error={form.touched.password ? form.errors.password : undefined}
                    type='password'
                    className='mt-1 block w-full'
                    required
                />
                <div className='flex items-center justify-end mt-6'>
                    <Button type='submit' variant='filled' color='accent'>
                        Sign in
                    </Button>
                </div>
            </LoginFormContainer>
        </form>
    )
}

export default LoginContainer
