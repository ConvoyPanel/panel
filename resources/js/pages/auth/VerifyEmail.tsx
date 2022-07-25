import React, { FormEvent } from 'react'
import Button from '@/components/Button'
import Guest from '@/components/layouts/Guest'
import { Head, Link, useForm } from '@inertiajs/inertia-react'

interface Props {
  status: string
}

export default function VerifyEmail({ status }: Props) {
  const { post, processing } = useForm()

  const submit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault()

    post(route('verification.send'))
  }

  return (
    <Guest>
      <Head title='Email Verification' />

      <div className='mb-4 text-sm text-gray-600'>
        Thanks for signing up! Before getting started, could you verify your
        email address by clicking on the link we just emailed to you? If you
        didn't receive the email, we will gladly send you another.
      </div>

      {status === 'verification-link-sent' && (
        <div className='mb-4 font-medium text-sm text-green-600'>
          A new verification link has been sent to the email address you
          provided during registration.
        </div>
      )}

      <form onSubmit={submit}>
        <div className='mt-4 flex items-center justify-between'>
          <Button processing={processing}>Resend Verification Email</Button>

          <Link
            href={route('logout')}
            method='post'
            as='button'
            className='underline text-sm text-gray-600 hover:text-gray-900'
          >
            Log Out
          </Link>
        </div>
      </form>
    </Guest>
  )
}
