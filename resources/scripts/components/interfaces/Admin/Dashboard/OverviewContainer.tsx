import { useQuery } from '@tanstack/react-query'
import {
    IconAlertTriangle,
    IconArchive,
    IconDeviceSdCard,
    IconDisc,
    IconMapPin,
    IconNetwork,
    IconServer,
    IconServerBolt,
    IconUsers,
    type TablerIcon,
} from '@tabler/icons-react'
import { ReactNode } from 'react'

import { oldFormatBytes } from '@/utils'

import { overviewQueries } from '@/features/overview/api'

import StatisticCard from '@/components/interfaces/Client/Server/Overview/StatisticCard.tsx'
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { LinearProgressBar } from '@/components/ui/Progress'
import Spinner from '@/components/ui/Spinner.tsx'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'
import { Heading } from '@/components/ui/Typography'

type Allocation = App.Data.Admin.Overview.ResourceAllocationData

const formatBytes = (bytes: number) => {
    const { size, unit } = oldFormatBytes(bytes)
    return `${size} ${unit}`
}

const StatTile = ({
    title,
    icon,
    value,
    caption,
}: {
    title: string
    icon: TablerIcon
    value: ReactNode
    caption?: ReactNode
}) => (
    <StatisticCard title={title} icon={icon}>
        <div className='text-2xl font-bold'>{value}</div>
        {caption && (
            <p className='text-xs text-muted-foreground'>{caption}</p>
        )}
    </StatisticCard>
)

const AllocationBar = ({ allocation }: { allocation: Allocation }) => (
    <div className='space-y-1'>
        <LinearProgressBar value={Math.min(allocation.percent, 100)} />
        <div className='flex justify-between text-xs text-muted-foreground'>
            <span>
                {formatBytes(allocation.allocated)} / {formatBytes(allocation.total)}
            </span>
            <span>{allocation.percent}%</span>
        </div>
    </div>
)

const CapacityCard = ({
    title,
    icon: Icon,
    allocation,
}: {
    title: string
    icon: TablerIcon
    allocation: Allocation
}) => (
    <Card>
        <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
            <CardTitle className='text-sm font-medium'>{title}</CardTitle>
            <Icon className='h-4 w-4 text-muted-foreground' />
        </CardHeader>
        <CardContent>
            <AllocationBar allocation={allocation} />
        </CardContent>
    </Card>
)

const CountRow = ({ label, value }: { label: string; value: number }) => (
    <div className='flex items-center justify-between text-sm'>
        <span className='text-muted-foreground'>{label}</span>
        <span className='font-medium tabular-nums'>{value}</span>
    </div>
)

const OverviewContainer = () => {
    const { data, isPending, isError } = useQuery(overviewQueries.metrics())

    if (isPending) {
        return (
            <div className='flex justify-center py-16'>
                <Spinner className='h-6 w-6' />
            </div>
        )
    }

    if (isError) {
        return (
            <p className='py-16 text-center text-sm text-muted-foreground'>
                Couldn't load the dashboard metrics. Try again shortly.
            </p>
        )
    }

    const { summary, servers, memory, storage, addresses, backups, isos, nodes } =
        data

    return (
        <div className='space-y-4 @container'>
            <Heading>Admin Dashboard</Heading>

            {/* Fleet summary */}
            <div className='grid grid-cols-2 gap-2 @md:gap-4 @lg:grid-cols-4'>
                <StatTile
                    title='Servers'
                    icon={IconServer}
                    value={summary.servers}
                    caption={
                        summary.failedServers > 0
                            ? `${summary.failedServers} failed`
                            : 'All healthy'
                    }
                />
                <StatTile title='Nodes' icon={IconServerBolt} value={summary.nodes} />
                <StatTile title='Users' icon={IconUsers} value={summary.users} />
                <StatTile
                    title='Locations'
                    icon={IconMapPin}
                    value={summary.locations}
                />
            </div>

            {/* Capacity */}
            <div className='grid grid-cols-1 gap-2 @md:gap-4 @lg:grid-cols-2'>
                <CapacityCard
                    title='Memory'
                    icon={IconDeviceSdCard}
                    allocation={memory}
                />
                <CapacityCard
                    title='Storage'
                    icon={IconDeviceSdCard}
                    allocation={storage}
                />
            </div>

            {/* Server status, addresses, backups, ISOs */}
            <div className='grid grid-cols-1 gap-2 @md:gap-4 @lg:grid-cols-2 @2xl:grid-cols-4'>
                <Card>
                    <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
                        <CardTitle className='text-sm font-medium'>Servers</CardTitle>
                        <IconServer className='h-4 w-4 text-muted-foreground' />
                    </CardHeader>
                    <CardContent className='space-y-1'>
                        <CountRow label='Ready' value={servers.ready} />
                        <CountRow label='Installing' value={servers.installing} />
                        <CountRow label='Suspended' value={servers.suspended} />
                        <CountRow label='Restoring' value={servers.restoring} />
                        <CountRow label='Deleting' value={servers.deleting} />
                        <CountRow label='Failed' value={servers.failed} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
                        <CardTitle className='text-sm font-medium'>
                            IP addresses
                        </CardTitle>
                        <IconNetwork className='h-4 w-4 text-muted-foreground' />
                    </CardHeader>
                    <CardContent className='space-y-2'>
                        <AllocationBar
                            allocation={{
                                allocated: addresses.assigned,
                                total: addresses.total,
                                percent: addresses.percent,
                            }}
                        />
                        <CountRow label='Pools' value={addresses.pools} />
                        <CountRow label='Available' value={addresses.available} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
                        <CardTitle className='text-sm font-medium'>Backups</CardTitle>
                        <IconArchive className='h-4 w-4 text-muted-foreground' />
                    </CardHeader>
                    <CardContent className='space-y-1'>
                        <CountRow label='Total' value={backups.total} />
                        <CountRow label='Successful' value={backups.successful} />
                        <CountRow label='Pending' value={backups.pending} />
                        <CountRow label='Failed' value={backups.failed} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
                        <CardTitle className='text-sm font-medium'>ISOs</CardTitle>
                        <IconDisc className='h-4 w-4 text-muted-foreground' />
                    </CardHeader>
                    <CardContent className='space-y-1'>
                        <CountRow label='Total' value={isos.total} />
                        <CountRow label='Ready' value={isos.successful} />
                        <CountRow label='Pending' value={isos.pending} />
                    </CardContent>
                </Card>
            </div>

            {/* Per-node breakdown */}
            <Card>
                <CardHeader className='pb-2'>
                    <CardTitle className='text-sm font-medium'>Nodes</CardTitle>
                </CardHeader>
                <CardContent>
                    {nodes.length === 0 ? (
                        <p className='py-4 text-center text-sm text-muted-foreground'>
                            No nodes yet.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Node</TableHead>
                                    <TableHead className='text-right'>Servers</TableHead>
                                    <TableHead className='w-1/2'>Memory</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {nodes.map((node) => (
                                    <TableRow key={node.id}>
                                        <TableCell>
                                            <div className='font-medium'>
                                                {node.displayName}
                                            </div>
                                            <div className='text-xs text-muted-foreground'>
                                                {node.fqdn}
                                            </div>
                                        </TableCell>
                                        <TableCell className='text-right tabular-nums'>
                                            {node.servers}
                                        </TableCell>
                                        <TableCell>
                                            <AllocationBar allocation={node.memory} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>

            {summary.failedServers > 0 && (
                <p className='flex items-center gap-1.5 text-xs text-muted-foreground'>
                    <IconAlertTriangle className='h-3.5 w-3.5' />
                    {summary.failedServers} server
                    {summary.failedServers === 1 ? '' : 's'} in a failed state.
                </p>
            )}
        </div>
    )
}

export default OverviewContainer
