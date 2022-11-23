import { Backup } from '@/api/server/backups/getBackups'
import Display from '@/components/elements/displays/DisplayRow'
import { bytesToString } from '@/util/helpers'
import { Loader } from '@mantine/core'
import { formatDistanceToNow } from 'date-fns'
//@ts-ignore
import Dots from '@/assets/images/icons/dots-vertical.svg'
import { ComponentProps } from 'react'
import Menu from '@/components/elements/Menu'
import { ExclaimationCircleIcon } from '@heroicons/react/20/solid'

interface Props {
  backup: Backup
}

const DottedButton = ({
  className,
  ...props
}: Omit<ComponentProps<'button'>, 'children'>) => {
  return (
    <button className={`px-2 ${className}`} {...props}>
      <img
        src={Dots}
        className='w-4 h-4 dark:invert'
        alt='3 vertical dots meant for activating a menu'
      />
    </button>
  )
}

interface DropdownProps {
  className?: string
  backup: Backup
}

const Dropdown = ({ className, backup }: DropdownProps) => {
  return (
    <Menu>
      <Menu.Button>
        <DottedButton className={className} />
      </Menu.Button>
      <Menu.Items>
        <Menu.Item>Restore</Menu.Item>
        <Menu.Item color='danger'>Delete</Menu.Item>
      </Menu.Items>
    </Menu>
  )
}

const BackupRow = ({ backup }: Props) => {
  return (
    <Display.Row
      key={backup.uuid}
      className='grid-cols-1 md:grid-cols-8 text-sm'
    >
      <div className='flex justify-between items-center md:col-span-5'>
        <div className='flex items-center space-x-3'>
          <p className='overflow-hidden text-ellipsis font-semibold text-foreground'>
            {backup.name}
          </p>
          {!backup.completedAt ? (
            <Loader size='xs' />
          ) : (
            !backup.successful && (
              <ExclaimationCircleIcon className='h-5 w-5 text-error' />
            )
          )}
        </div>

        {backup.completedAt && backup.successful ? (
          <Dropdown backup={backup} className='md:hidden' />
        ) : (
          ''
        )}
      </div>
      <div>{bytesToString(backup.size)}</div>
      <div className='flex justify-between items-center md:col-span-2'>
        <p>
          {formatDistanceToNow(backup.createdAt, {
            includeSeconds: true,
            addSuffix: true,
          })}
        </p>

        {backup.completedAt && backup.successful ? (
          <Dropdown backup={backup} className='hidden md:block' />
        ) : (
          ''
        )}
      </div>
    </Display.Row>
  )
}

export default BackupRow
