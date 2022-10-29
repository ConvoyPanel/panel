import login from '@/api/auth/login'
import LoginFormContainer from '@/components/auth/LoginFormContainer'
import { Button, TextInput } from '@mantine/core'
import { useFormik } from 'formik'
import * as yup from 'yup'
import useFlash from '@/util/useFlash'

const LoginContainer = () => {
  const { clearFlashes, clearAndAddHttpError } = useFlash();

  const rules = yup.object({
    email: yup
      .string()
      .email('Enter a valid email')
      .required('Email is required'),
    password: yup.string().required('Password is required'),
  });

  const form = useFormik({
    initialValues: {
      email: '',
      password: '',
    },
    validationSchema: rules,
    onSubmit: (values) => {
      clearFlashes()

      login(values).then(response => {
        window.location.replace('/')

      }).catch(error => {
        console.log(error)

        form.setSubmitting(false)
        clearAndAddHttpError({ error })
      })
    }
  })
  return (
    <form onSubmit={form.handleSubmit}>
      <LoginFormContainer title='Convoy' description='Sign in to your account'>
        <TextInput
          label='Email'
          name='email'
          styles={{
            required: { display: 'none' },
          }}
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
          styles={{
            required: { display: 'none' },
          }}
          value={form.values.password}
          onChange={form.handleChange}
          error={form.touched.password ? form.errors.password : undefined}
          type='password'
          className='mt-1 block w-full'
          required
        />
        <div className='flex items-center justify-end mt-6'>
          <Button type='submit'>Sign in</Button>
        </div>
      </LoginFormContainer>
    </form>
  )
}

export default LoginContainer
