import LoginFormContainer from '@/components/auth/LoginFormContainer'
import { Button, TextInput } from '@mantine/core'

const LoginContainer = () => {
  return (
    <LoginFormContainer title='Convoy' description='Sign in to your account'>
      <TextInput
        label='Email'
        name='email'
        styles={{
          required: { display: 'none' },
        }}
        className='mt-1 block w-full'
        autoFocus
        required
      />
      <TextInput
        label='Password'
        name='password'
        styles={{
          required: { display: 'none' },
        }}
        type='password'
        className='mt-1 block w-full'
        required
      />
      <div className='flex items-center justify-end mt-6'>
        <Button type='submit'>Sign in</Button>
      </div>
    </LoginFormContainer>
  )
}

export default LoginContainer
