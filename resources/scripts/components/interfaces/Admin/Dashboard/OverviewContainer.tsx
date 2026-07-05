import { useQuery } from '@tanstack/react-query'
import byteSize from 'byte-size'
import { ReactNode } from 'react'

import { overviewQueries } from '@/features/overview/api'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
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

const bytes = (value: number) => {
    const { value: size, unit } = byteSize(value, { units: 'iec', precision: 1 })
    return `${size} ${unit}`
}

/** A single label/value pair — the app's dense definition-list stat (see SpecificationsCard). */
const Stat = ({ label, value }: { label: ReactNode; value: ReactNode }) => (
    <div>
        <dt className='text-xs text-muted-foreground'>{label}</dt>
        <dd className='text-lg font-semibold tabular-nums'>{value}</dd>
    </div>
)

const CapacityRow = ({
    label,
    allocation,
}: {
    label?: string
    allocation: Allocation
}) => (
    <div className='space-y-1.5'>
        <div className='flex justify-between text-xs text-muted-foreground'>
            <span>{label}</span>
            <span className='tabular-nums'>
                {bytes(allocation.allocated)} / {bytes(allocation.total)} ·{' '}
                {allocation.percent}%
            </span>
        </div>
        <LinearProgressBar value={Math.min(allocation.percent, 100)} />
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
        <div className='@container space-y-4'>
            <Heading>Admin Dashboard</Heading>

            {/* At a glance — every top-line count in one dense card. */}
            <Card>
                <CardHeader className='pb-3'>
                    <CardTitle>At a glance</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl className='grid grid-cols-3 gap-4 @lg:grid-cols-6'>
                        <Stat label='Servers' value={summary.servers} />
                        <Stat label='Nodes' value={summary.nodes} />
                        <Stat label='Users' value={summary.users} />
                        <Stat label='Locations' value={summary.locations} />
                        <Stat label='IP addresses' value={addresses.total} />
                        <Stat label='Failed servers' value={summary.failedServers} />
                    </dl>
                </CardContent>
            </Card>

            <div className='grid gap-4 @lg:grid-cols-2'>
                {/* Fleet capacity */}
                <Card>
                    <CardHeader className='pb-3'>
                        <CardTitle>Capacity</CardTitle>
                    </CardHeader>
                    <CardContent className='space-y-4'>
                        <CapacityRow label='Memory' allocation={memory} />
                        <CapacityRow label='Storage' allocation={storage} />
                    </CardContent>
                </Card>

                {/* Servers by status */}
                <Card>
                    <CardHeader className='pb-3'>
                        <CardTitle>Servers by status</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className='grid grid-cols-3 gap-4'>
                            <Stat label='Ready' value={servers.ready} />
                            <Stat label='Installing' value={servers.installing} />
                            <Stat label='Suspended' value={servers.suspended} />
                            <Stat label='Restoring' value={servers.restoring} />
                            <Stat label='Deleting' value={servers.deleting} />
                            <Stat label='Failed' value={servers.failed} />
                        </dl>
                    </CardContent>
                </Card>
            </div>

            <div className='grid gap-4 @md:grid-cols-3'>
                {/* IP addresses — counts, not bytes */}
                <Card>
                    <CardHeader className='pb-3'>
                        <CardTitle>IP addresses</CardTitle>
                    </CardHeader>
                    <CardContent className='space-y-3'>
                        <div className='space-y-1.5'>
                            <div className='flex justify-between text-xs text-muted-foreground'>
                                <span>
                                    {addresses.assigned} / {addresses.total} assigned
                                </span>
                                <span>{addresses.percent}%</span>
                            </div>
                            <LinearProgressBar
                                value={Math.min(addresses.percent, 100)}
                            />
                        </div>
                        <dl className='grid grid-cols-2 gap-4'>
                            <Stat label='Pools' value={addresses.pools} />
                            <Stat label='Available' value={addresses.available} />
                        </dl>
                    </CardContent>
                </Card>

                {/* Backups */}
                <Card>
                    <CardHeader className='pb-3'>
                        <CardTitle>Backups</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className='grid grid-cols-2 gap-4'>
                            <Stat label='Total' value={backups.total} />
                            <Stat label='Successful' value={backups.successful} />
                            <Stat label='Pending' value={backups.pending} />
                            <Stat label='Failed' value={backups.failed} />
                        </dl>
                    </CardContent>
                </Card>

                {/* ISOs */}
                <Card>
                    <CardHeader className='pb-3'>
                        <CardTitle>ISOs</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className='grid grid-cols-3 gap-4'>
                            <Stat label='Total' value={isos.total} />
                            <Stat label='Ready' value={isos.successful} />
                            <Stat label='Pending' value={isos.pending} />
                        </dl>
                    </CardContent>
                </Card>
            </div>

            {/* Per-node breakdown */}
            <Card>
                <CardHeader className='pb-3'>
                    <CardTitle>Nodes</CardTitle>
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
                                            <CapacityRow allocation={node.memory} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </div>
    )
}

export default OverviewContainer
