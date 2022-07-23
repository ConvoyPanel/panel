import { PlusIcon } from '@heroicons/react/solid'
import { Button } from '@mantine/core'
import { ComponentProps } from 'react'

interface Props {
  icon: (props: ComponentProps<'svg'>) => JSX.Element
  title: string
  description: string
  action: string
  onClick: () => void
}

const EmptyState = ({icon: Icon, title, description, action, onClick}: Props) => {
  return <div className='flex flex-col justify-center items-center text-center h-[60vh]'>
    <Icon
      className='mx-auto h-12 w-12 text-gray-400'
      aria-hidden='true'
    />
    <h3 className='mt-2 text-sm font-medium text-gray-900'>{ title }</h3>
    <p className='mt-1 text-sm text-gray-500'>
      { description }
    </p>
    <div className='mt-6'>
      <Button onClick={onClick} type='button'>
        <PlusIcon className='-ml-1 mr-2 h-5 w-5' aria-hidden='true' />
        { action }
      </Button>
    </div>
  </div>
}

export default EmptyState
