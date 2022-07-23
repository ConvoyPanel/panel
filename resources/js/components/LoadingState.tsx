import { Loader } from '@mantine/core'

interface Props {
    title?: string
}

const LoadingState = ({ title }: Props) => {
  return (
    <div className='grid place-items-center h-[30vh] w-full'>
      <div className='flex flex-col space-y-3 items-center'>
        <Loader />
        <h3 className='h3-deemphasized'>{ title ? title : 'Connecting'}</h3>
      </div>
    </div>
  )
}

export default LoadingState
