import { ComponentProps } from 'react'

const RoundedButton = ({ children, ...props }: ComponentProps<'button'>) => {
  return (
    <button
      {...props}
      className='w-8 h-8 bg-white inline-flex items-center justify-center text-gray-400 rounded-full bg-transparent hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
    >
      {children}
    </button>
  )
}
export default RoundedButton
