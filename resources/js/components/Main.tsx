import { ComponentProps } from 'react'

const Main = ({children, ...props}: ComponentProps<'div'>) => {
  return (
    <div className='py-12' {...props}>
      <div className='max-w-7xl mx-auto sm:px-6 lg:px-8'>
        {children}
      </div>
    </div>
  )
}

export default Main
