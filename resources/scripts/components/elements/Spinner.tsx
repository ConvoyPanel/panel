import { Loader } from '@mantine/core'
import { ReactNode, Suspense } from 'react'

interface Spinner extends React.FC {
  Suspense: React.FC<{
    children: ReactNode
  }>
}

const Spinner: Spinner = () => {
  return (
    <div className='grid place-items-center w-full h-screen'>
      <Loader size='lg' />
    </div>
  )
}

Spinner.Suspense = ({ children }) => {
  return <Suspense fallback={<Spinner />}>{children}</Suspense>
}

export default Spinner
