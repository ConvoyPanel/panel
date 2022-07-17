import { KeyIcon, ServerIcon, WifiIcon } from '@heroicons/react/outline'
import { Modal } from '@mantine/core'
import { ComponentProps, useEffect, useState } from 'react'

export interface Props {
  opened: boolean
}

export interface SolutionProps extends ComponentProps<'div'> {
  icon: (props: ComponentProps<'svg'>) => JSX.Element
  title: string
  description: string
}

export const Solution = ({ icon: Icon, title, description }: SolutionProps) => {
  return (
    <div className='grid md:grid-cols-10 gap-3'>
      <Icon className='hidden md:block col-span-1 w-full text-blue-600' />
      <div className='col-span-9'>
        <h4 className='h4'>{title}</h4>
        <p className='p-desc'>{description}</p>
      </div>
    </div>
  )
}

const ServerUnavailableModal = ({ opened }: Props) => {
  const [visible, setVisible] = useState(opened)

  useEffect(() => {
    setVisible(opened)
  }, [opened])

  return (
    <Modal
      title='Server Unavailable'
      opened={visible}
      onClose={() => setVisible(false)}
      centered
    >
      <p className='p-desc'>
        This server is unreachable. Please check you have done the following
        solutions:
      </p>
      <div className='flex flex-col space-y-3 mt-3'>
        <Solution
          icon={WifiIcon}
          title='Network'
          description='Make sure you are connected to internet or the intranet where this panel is hosted.'
        />
        <Solution
          icon={KeyIcon}
          title='Permissions'
          description='Make sure you have permission to access this panel or server. You may need to contact the server administrator.'
        />
        <Solution icon={ServerIcon} title='Is it running?' description="If you're an administrator, make sure the node is running or is accessible by this panel." />
      </div>
    </Modal>
  )
}

export default ServerUnavailableModal
