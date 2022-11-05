import { Server } from '@/api/server/getServer'
import { formatBytes, getInitials } from '@/util/helpers'
import styled from '@emotion/styled'
import { Avatar, Badge } from '@mantine/core'
import { useMemo } from 'react'
import { Link } from 'react-router-dom'
import tw from 'twin.macro'

interface Props {
  server: Server
}

const Dt = styled.dt`
${tw`dark:text-stone-400 text-stone-500 text-xs`}
`

const Dd = styled.dt`
${tw`text-sm font-semibold`}
`

const ServerCard = ({ server }: Props) => {
  const memory = useMemo(() => formatBytes(server.limits.memory, 0), [server])

  const disk = useMemo(() => formatBytes(server.limits.disk, 0), [server])

  return (
    <Link to={`/servers/${server.identifier}`} className='bg-auto p-6 shadow hover:shadow-lg border border-colors border-colors-hover dark:shadow-none dark:hover:shadow-none transition-shadow rounded-lg'>
      <div className='flex items-center space-x-3'>
        <Avatar color='blue' size='md' radius='xl'>
          {getInitials(server.name, ' ', 2)}
        </Avatar>
        <div>
          <div className='flex items-center space-x-3'>
            <p className='font-medium text-auto'>{server.name}</p>{' '}
            {server.config.template && (
              <Badge color='orange' radius='sm'>
                Template
              </Badge>
            )}
          </div>
          {/* <p className='text-sm description'>A lovely server</p> */}
        </div>
      </div>

      <div className='grid grid-cols-3 gap-3 mt-3'>
        <dl>
          <Dt className='dt-small'>CPU</Dt>
          <Dd className='dd-small'>{ server.limits.cpu }</Dd>
        </dl>
        <dl>
          <Dt className='dt-small'>Memory</Dt>
          <Dd className='dd-small'>{memory.size} {memory.unit}</Dd>
        </dl>
        <dl>
          <Dt className='dt-small'>Disk</Dt>
          <Dd className='dd-small'>{disk.size} {disk.unit}</Dd>
        </dl>
      </div>
    </Link>
  )
}

export default ServerCard
