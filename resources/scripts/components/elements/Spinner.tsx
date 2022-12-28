import { Loader } from '@mantine/core'
import { ReactNode, Suspense } from 'react'

interface Props {
  screen?: boolean
  flat?: boolean
}

interface Spinner extends React.FC<Props> {
  Suspense: React.FC<{
    children: ReactNode
    screen?: boolean
  }>
}

const Spinner: Spinner = ({ screen, flat }: Props) => {
  return (
    <div className={`grid place-items-center w-full ${screen ? 'h-screen' : 'h-40'} ${flat && 'dark:bg-black'}`}>
      <Loader size='lg' />
    </div>
  )
}

Spinner.Suspense = ({ children, screen }) => {
  return <Suspense fallback={<Spinner screen={ screen } />}>{children}</Suspense>
}

export default Spinner
