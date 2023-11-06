import { ExclamationCircleIcon } from '@heroicons/react/24/outline'
import { ReactNode } from 'react'

interface Props {
    children?: ReactNode
}

const ErrorMessage = ({ children }: Props) => (
    <div className='flex space-x-1 mt-2'>
        <ExclamationCircleIcon className='h-5 w-5 text-error' />{' '}
        <p className='text-sm text-error'>{children}</p>
    </div>
)

export default ErrorMessage
