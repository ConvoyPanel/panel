import byteSize from 'byte-size'
import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { IconBolt } from '@tabler/icons-react'

import { useServer } from '@/features/servers/admin/api.ts'
import { serverStateQueries } from '@/features/servers/state/api.ts'

import ServerPowerActions from '@/features/servers/components/admin/ServerPowerActions.tsx'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

import { ServerLifecycleStatus } from '@/types/server.ts'

interface Props {
    serverId: number
}

const STATUS_LABELS: Record<ServerLifecycleStatus, string> = {
    [ServerLifecycleStatus.Ready]: 'Ready',
    [ServerLifecycleStatus.DeferredOsSelection]: 'Awaiting OS selection',
    [ServerLifecycleStatus.Installing]: 'Installing',
    [ServerLifecycleStatus.InstallFailed]: 'Install failed',
    [ServerLifecycleStatus.Suspended]: 'Suspended',
    [ServerLifecycleStatus.RestoringBackup]: 'Restoring backup',
    [ServerLifecycleStatus.Deleting]: 'Deleting',
    [ServerLifecycleStatus.DeletionFailed]: 'Deletion failed',
}

const formatBytes = (bytes: number) => {
    const size = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${size.value} ${size.unit}`
}

const formatDuration = (seconds: number) => {
    if (seconds <= 0) return '—'

    const days = Math.floor(seconds / 86400)
    const hours = Math.floor((seconds % 86400) / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)

    return [days && `${days}d`, hours && `${hours}h`, `${minutes}m`]
        .filter(Boolean)
        .join(' ')
}

const ServerDetailOverview = ({ serverId }: Props) => {
    const { data: server } = useServer(serverId)
    const { data: state } = useQuery({
        ...serverStateQueries.detail(server?.uuid ?? ''),
        enabled: !!server?.uuid,
    })

    const specs = server && [
        { title: 'vCPU Cores', value: server.cpu },
        { title: 'Memory', value: formatBytes(server.memory) },
        { title: 'Disk', value: formatBytes(server.disk) },
        {
            title: 'Bandwidth',
            value:
                server.bandwidth.limit > 0
                    ? formatBytes(server.bandwidth.limit)
                    : 'Unlimited',
        },
    ]

    const details = server && [
        {
            title: 'Node',
            value: server.node ? (
                <Link
                    className='text-primary hover:underline'
                    to={`/admin/nodes/${server.nodeId}` as string}
                >
                    {server.node.displayName}
                </Link>
            ) : (
                server.nodeId
            ),
        },
        { title: 'Owner (user ID)', value: server.userId },
        { title: 'VMID', value: server.vmid },
        { title: 'UUID', value: <span className='font-mono text-xs'>{server.uuid}</span> },
        { title: 'Created', value: server.createdAt.toLocaleString() },
        { title: 'Description', value: server.description || '—' },
    ]

    return (
        <>
            <div className='flex flex-wrap items-center justify-between gap-3'>
                <div className='space-y-1'>
                    <div className='flex items-center gap-3'>
                        {server ? (
                            <Heading>{server.name}</Heading>
                        ) : (
                            <Skeleton className='h-8 w-48' />
                        )}
                        {server && (
                            <Badge variant='outline'>
                                {STATUS_LABELS[server.status] ?? server.status}
                            </Badge>
                        )}
                    </div>
                    {server && (
                        <p className='text-sm text-muted-foreground'>
                            {server.hostname}
                        </p>
                    )}
                </div>

                {server && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button>
                                <IconBolt className='mr-2 size-4' />
                                Power
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align='end'>
                            <ServerPowerActions server={server} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>

            <div className='grid grid-cols-1 gap-4 @md:grid-cols-2 @xl:grid-cols-3'>
                <Card>
                    <CardHeader>
                        <CardTitle>Live state</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {state ? (
                            <dl className='grid grid-cols-2 gap-y-3 text-sm'>
                                <dt className='text-muted-foreground'>Power</dt>
                                <dd className='text-right font-medium'>
                                    {state.state === 'running'
                                        ? 'Running'
                                        : 'Stopped'}
                                </dd>
                                <dt className='text-muted-foreground'>Uptime</dt>
                                <dd className='text-right'>
                                    {formatDuration(state.uptime)}
                                </dd>
                                <dt className='text-muted-foreground'>CPU</dt>
                                <dd className='text-right'>
                                    {(state.cpuUsed * 100).toFixed(1)}%
                                </dd>
                                <dt className='text-muted-foreground'>Memory</dt>
                                <dd className='text-right'>
                                    {formatBytes(state.memoryUsed)} /{' '}
                                    {formatBytes(state.memoryTotal)}
                                </dd>
                            </dl>
                        ) : (
                            <Skeleton className='h-24 w-full' />
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Specifications</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {specs ? (
                            <dl className='grid grid-cols-2 gap-y-3 text-sm'>
                                {specs.map(spec => (
                                    <div key={spec.title} className='contents'>
                                        <dt className='text-muted-foreground'>
                                            {spec.title}
                                        </dt>
                                        <dd className='text-right'>
                                            {spec.value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        ) : (
                            <Skeleton className='h-24 w-full' />
                        )}
                    </CardContent>
                </Card>

                <Card className='@md:col-span-2 @xl:col-span-1'>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {details ? (
                            <dl className='grid grid-cols-[auto_1fr] gap-x-4 gap-y-3 text-sm'>
                                {details.map(detail => (
                                    <div key={detail.title} className='contents'>
                                        <dt className='text-muted-foreground'>
                                            {detail.title}
                                        </dt>
                                        <dd className='text-right'>
                                            {detail.value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        ) : (
                            <Skeleton className='h-32 w-full' />
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    )
}

export default ServerDetailOverview
