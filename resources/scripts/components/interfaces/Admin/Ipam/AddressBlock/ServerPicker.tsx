import { Server } from '@/types/server.ts'
import { cn } from '@/utils'
import { IconCheck, IconServer } from '@tabler/icons-react'
import { useController } from 'react-hook-form'

import getCompatibleServers from '@/api/admin/addressBlockGroups/addressBlocks/addresses/getCompatibleServers.ts'
import useServer from '@/api/admin/servers/use-server.ts'

import { ResourceComboboxForm } from '@/components/ui/Forms'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    addressBlockGroupId: number
}

const ServerPicker = ({ addressBlockGroupId }: Props) => {
    const { field } = useController<{
        serverId: string
    }>({
        name: 'serverId',
    })
    const { data: selectedServer, isLoading: isLoadingSelection } =
        useServer(field.value ? Number(field.value) : null)

    return (
        <ResourceComboboxForm<Server>
            swrKey={`compatible-servers-${addressBlockGroupId}`}
            accessorKey={'id'}
            name={'serverId'}
            fetcher={(query, page) =>
                getCompatibleServers(
                    addressBlockGroupId,
                    {
                        page: page,
                        filters: {
                            '*': query,
                        },
                    },
                    ['node']
                )
            }
            renderItem={(server, isSelected) => (
                <>
                    <dl className={'flex grow flex-col overflow-hidden'}>
                        <dt className={'truncate'}>{server.name}</dt>
                        <dd
                            className={'truncate text-xs text-muted-foreground'}
                        >
                            {server.node?.name}
                        </dd>
                    </dl>

                    <IconCheck
                        className={cn(
                            'shrink-0',
                            isSelected ? 'opacity-100' : 'opacity-0'
                        )}
                    />
                </>
            )}
            renderTrigger={() => (
                <>
                    {isLoadingSelection ? (
                        <Skeleton className={'h-3 w-24'} />
                    ) : selectedServer ? (
                        selectedServer.name
                    ) : (
                        'Select a server'
                    )}

                    <IconServer className={'ml-auto size-4 opacity-50'} />
                </>
            )}
            label={'Server'}
            searchPlaceholder={'Search servers...'}
        />
    )
}

export default ServerPicker
