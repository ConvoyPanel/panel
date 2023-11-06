import { formatBytes, getInitials } from '@/util/helpers'
import styled from '@emotion/styled'
import { Avatar, Badge } from '@mantine/core'
import { useMemo } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import tw from 'twin.macro'

import { ServerBuild } from '@/api/server/getServer'

interface Props {
    server: ServerBuild
}

export const Dt = styled.dt`
    ${tw`text-accent-500 font-medium text-xs`}
`

export const Dd = styled.dt`
    ${tw`text-sm text-foreground font-medium`}
`

const ServerCard = ({ server }: Props) => {
    const { t: tStrings } = useTranslation('strings')
    const memory = useMemo(() => formatBytes(server.limits.memory, 0), [server])

    const disk = useMemo(() => formatBytes(server.limits.disk, 0), [server])

    return (
        <Link
            to={`/servers/${server.id}`}
            className='overflow-hidden bg-background p-6 shadow-light hover:shadow-lg border border-accent-200 sm:dark:hover:border-foreground dark:active:border-foreground dark:shadow-none dark:hover:shadow-none transition-shadow rounded-lg'
        >
            <div className='flex items-center space-x-3'>
                <Avatar color='dark' size='md' radius='xl'>
                    {getInitials(server.name, ' ', 2)}
                </Avatar>
                <div className='overflow-hidden'>
                    <div className='flex items-center space-x-3'>
                        <p className='font-medium text-foreground truncate'>
                            {server.name}
                        </p>{' '}
                        {server.status === 'suspended' && (
                            <Badge color='orange' radius='sm'>
                                {tStrings('suspended')}
                            </Badge>
                        )}
                    </div>
                    <p className='text-sm description text-ellipsis overflow-hidden whitespace-nowrap'>
                        {server.hostname}
                    </p>
                </div>
            </div>

            <div className='grid grid-cols-3 gap-3 mt-3'>
                <dl>
                    <Dt>{tStrings('cpu')}</Dt>
                    <Dd>{server.limits.cpu}</Dd>
                </dl>
                <dl>
                    <Dt>{tStrings('memory')}</Dt>
                    <Dd>
                        {memory.size} {memory.unit}
                    </Dd>
                </dl>
                <dl>
                    <Dt>{tStrings('disk')}</Dt>
                    <Dd>
                        {disk.size} {disk.unit}
                    </Dd>
                </dl>
            </div>
        </Link>
    )
}

export default ServerCard
