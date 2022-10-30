import { Loader } from '@mantine/core'
import { ReactNode, Suspense } from 'react'

interface Props {
  screen?: boolean
}

interface Spinner extends React.FC<Props> {
  Suspense: React.FC<{
    children: ReactNode
  }>
}

const Spinner: Spinner = ({ screen }: Props) => {
  return (
    <div className={`grid place-items-center w-full ${screen ? 'h-screen' : 'h-40'} dark:bg-black`}>
      <Loader size='lg' />
    </div>
  )
}

Spinner.Suspense = ({ children }) => {
  return <Suspense fallback={<Spinner screen />}>{children}</Suspense>
}

export default Spinner
