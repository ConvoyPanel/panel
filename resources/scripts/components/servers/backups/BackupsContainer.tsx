import getBackups from '@/api/server/backups/getBackups'
import Display from '@/components/elements/displays/DisplayRow'
import Modal from '@/components/elements/Modal'
import Spinner from '@/components/elements/Spinner'
import ServerContentBlock from '@/components/servers/ServerContentBlock'
import { ServerContext } from '@/state/server'
import { bytesToString } from '@/util/helpers'
import { Button, TextInput } from '@mantine/core'
import { formatDistanceToNow } from 'date-fns'
import { useState } from 'react'
import useSWR from 'swr'

const BackupsContainer = () => {
  const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid)
  const { data } = useSWR(['server:backups', uuid], () => getBackups(uuid, {}))
  const [open, setOpen] = useState(false)

  return (
    <ServerContentBlock title='Backups'>
      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title='Create a Backup'
        description='Creating a backup will take a copy of your server files. This can take a while depending on the size of your server.'
      >
        <TextInput type='text' label='Name' autoFocus={false}/>
      </Modal>
      <div className='flex justify-end mb-3'>
        <Button onClick={() => setOpen(true)}>New Backup</Button>
      </div>
      {!data ? (
        <Spinner />
      ) : data.pagination.total === 0 ? (
        <p className='text-sm text-center'>There are no backups</p>
      ) : (
        <Display.Group>
          {data.items.map((backup) => (
            <Display.Row
              key={backup.uuid}
              className='grid-cols-1 md:grid-cols-8 text-sm'
            >
              <div className='md:col-span-5'>
                <p className='overflow-hidden text-ellipsis font-semibold text-foreground'>
                  {backup.name}
                </p>
              </div>
              <div>{bytesToString(backup.size)}</div>
              <div className='md:col-span-2'>
                {formatDistanceToNow(backup.createdAt, {
                  includeSeconds: true,
                  addSuffix: true,
                })}
              </div>
            </Display.Row>
          ))}
        </Display.Group>
      )}
    </ServerContentBlock>
  )
}

export default BackupsContainer
