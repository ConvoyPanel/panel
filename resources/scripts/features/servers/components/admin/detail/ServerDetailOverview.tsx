import { useServer, useUnflagServer } from '@/features/servers/admin/api.ts'
import ServerPowerActions from '@/features/servers/components/admin/ServerPowerActions.tsx'
import { serverStateQueries } from '@/features/servers/state/api.ts'
import { ServerLifecycle } from '@/types/server.ts'
import { IconBolt } from '@tabler/icons-react'
import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import byteSize from 'byte-size'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import CopyValue from '@/components/ui/CopyValue.tsx'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/Tooltip'
import { Heading } from '@/components/ui/Typography'

interface Props {
    serverId: number
}

const LIFECYCLE_LABELS: Record<ServerLifecycle, string> = {
    [ServerLifecycle.Ready]: 'Ready',
    [ServerLifecycle.DeferredOsSelection]: 'Awaiting OS selection',
    [ServerLifecycle.Installing]: 'Installing',
    [ServerLifecycle.InstallFailed]: 'Install failed',
    [ServerLifecycle.RestoringBackup]: 'Restoring backup',
    [ServerLifecycle.Deleting]: 'Deleting',
    [ServerLifecycle.DeletionFailed]: 'Deletion failed',
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
    const unflagServer = useUnflagServer()
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
        {
            title: 'Owner (user ID)',
            value: (
                <CopyValue
                    label={'user ID'}
                    value={String(server.userId)}
                    className={'tabular-nums'}
                />
            ),
        },
        {
            title: 'VMID',
            value: (
                <CopyValue
                    label={'VMID'}
                    value={String(server.vmid)}
                    className={'tabular-nums'}
                />
            ),
        },
        {
            title: 'UUID',
            value: (
                <CopyValue
                    label={'UUID'}
                    value={server.uuid}
                    className={'text-xs'}
                />
            ),
        },
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
                        {/* Two badges, never one: suspension sits alongside the
                            lifecycle rather than replacing it, so a suspended
                            server still shows the stage it is actually in. */}
                        {server && (
                            <Badge variant='outline'>
                                {LIFECYCLE_LABELS[server.lifecycle] ??
                                    server.lifecycle}
                            </Badge>
                        )}
                        {server?.suspendedAt && (
                            <Badge variant='destructive'>Suspended</Badge>
                        )}
                        {server?.flaggedAt && (
                            <>
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Badge variant='destructive'>
                                                Flagged
                                            </Badge>
                                        </TooltipTrigger>
                                        <TooltipContent className='max-w-72'>
                                            {server.flagReason ??
                                                'Flagged for review.'}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <Button
                                    variant='outline'
                                    size='sm'
                                    disabled={unflagServer.isPending}
                                    onClick={() =>
                                        unflagServer.mutate(server.uuid, {
                                            onSuccess: () =>
                                                toast.add({
                                                    title: 'Flag cleared',
                                                    type: 'success',
                                                }),
                                            onError: () =>
                                                toast.add({
                                                    title: 'Failed to clear the flag',
                                                    type: 'error',
                                                }),
                                        })
                                    }
                                >
                                    Clear flag
                                </Button>
                            </>
                        )}
                    </div>
                    {server && (
                        <p className='text-muted-foreground text-sm'>
                            {server.hostname}
                        </p>
                    )}
                </div>

                {server && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button>
                                <IconBolt className='size-4' />
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
                                    {state.powerState === 'running'
                                        ? 'Running'
                                        : 'Stopped'}
                                </dd>
                                <dt className='text-muted-foreground'>
                                    Uptime
                                </dt>
                                <dd className='text-right'>
                                    {formatDuration(state.uptime)}
                                </dd>
                                <dt className='text-muted-foreground'>CPU</dt>
                                <dd className='text-right'>
                                    {(state.cpuUsed * 100).toFixed(1)}%
                                </dd>
                                <dt className='text-muted-foreground'>
                                    Memory
                                </dt>
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
                                    <div
                                        key={detail.title}
                                        className='contents'
                                    >
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
