import PowerStateBadge from '@/features/servers/components/PowerStateBadge.tsx'
import { Route as ServerIndexRoute } from '@/routes/_app/servers.$serverUuid.tsx'
import { IconDots } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import byteSize from 'byte-size'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'

interface Props {
    server: App.Data.Server.ServerData
}

const ServerCard = ({ server }: Props) => {
    const memory = byteSize(server.memory, { units: 'iec', precision: 0 })
    const disk = byteSize(server.disk, { units: 'iec', precision: 0 })
    const specs = [
        { label: 'CPU', value: `${server.cpu} vCPU` },
        { label: 'Memory', value: `${memory.value} ${memory.unit}` },
        { label: 'Disk', value: `${disk.value} ${disk.unit}` },
    ]

    return (
        <Item variant={'muted'} size={'sm'}>
            <ItemContent className={'min-w-0 overflow-x-hidden'}>
                <ItemTitle className={'max-w-full'}>
                    <Link
                        to={ServerIndexRoute.to}
                        params={{
                            serverUuid: server.uuidShort,
                        }}
                        className={'truncate'}
                    >
                        {server.name}
                    </Link>
                    {/* `ready` is the resting state of every healthy server, so
                        badging it says nothing -- a badge here means "this one
                        needs your attention". */}
                    {server.status !== 'ready' && (
                        <Badge
                            variant={'secondary'}
                            className={'shrink-0 capitalize'}
                        >
                            {server.status.replace(/_/g, ' ')}
                        </Badge>
                    )}
                    <PowerStateBadge state={server.powerState} />
                </ItemTitle>
                <ItemDescription className={'truncate'}>
                    {server.hostname}
                </ItemDescription>
            </ItemContent>
            <dl
                className={
                    'order-last grid w-full grid-cols-3 gap-2 sm:order-none sm:w-auto sm:min-w-72 sm:gap-4'
                }
            >
                {specs.map(spec => (
                    <div key={spec.label}>
                        <dt className={'text-muted-foreground text-xs'}>
                            {spec.label}
                        </dt>
                        <dd className={'text-sm'}>{spec.value}</dd>
                    </div>
                ))}
            </dl>
            <ItemActions className={'ml-auto'}>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            size={'icon'}
                            variant={'ghost'}
                            aria-label={'Open server actions'}
                        >
                            <IconDots className={'text-muted-foreground'} />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align='end' className={'w-60'}>
                        <DropdownMenuItem>SSH to machine</DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuItem>Edit hostname</DropdownMenuItem>
                            <DropdownMenuItem>
                                Edit security settings
                            </DropdownMenuItem>
                            <DropdownMenuItem>
                                Edit networking settings
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem>Rebuild server</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </ItemActions>
        </Item>
    )
}

export default ServerCard
