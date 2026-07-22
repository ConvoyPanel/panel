import { IconServerBolt } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import { formatDistanceToNow } from 'date-fns'

import { Badge } from '@/components/ui/Badge'
import { buttonVariants } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemContent,
    ItemDescription,
    ItemGroup,
    ItemTitle,
} from '@/components/ui/Item'
import { LinearProgressBar } from '@/components/ui/Progress'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'
import { StatLabel } from '@/components/ui/Typography'

import {
    bytes,
    capacityTone,
    meterIndicatorClass,
    num,
} from './overview-helpers'

type NodeSummary = App.Data.Admin.Overview.NodeSummaryData
type ResourceUsage = App.Data.Admin.Overview.ResourceUsageData

const Meter = ({ percent, label }: { percent: number; label: string }) => (
    <LinearProgressBar
        value={Math.min(percent, 100)}
        aria-label={`${label}: ${percent}%`}
        indicatorClassName={meterIndicatorClass[capacityTone(percent)]}
    />
)

const Usage = ({
    usage,
    label,
    sub,
}: {
    usage: ResourceUsage
    label: string
    sub?: string
}) => (
    <div className='min-w-32'>
        <div className='mb-1.5 flex items-baseline justify-between gap-2'>
            <span className='text-sm font-semibold tabular-nums'>
                {usage.percent}%
            </span>
            <StatLabel as='span' className='text-xs whitespace-nowrap tabular-nums'>
                {bytes(usage.used)} / {bytes(usage.total)}
            </StatLabel>
        </div>
        <Meter percent={usage.percent} label={label} />
        {sub && <StatLabel className='mt-1.5 text-xs'>{sub}</StatLabel>}
    </div>
)

const statusClasses: Record<NodeSummary['status'], string> = {
    online: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
    unreachable: 'bg-destructive/15 text-destructive',
    unknown: 'bg-muted text-muted-foreground',
}

const StatusBadge = ({ node }: { node: NodeSummary }) => (
    <Badge
        variant='secondary'
        className={`${statusClasses[node.status]} shrink-0 capitalize`}
    >
        {node.status}
    </Badge>
)

const snapshotAge = (node: NodeSummary) =>
    node.resources
        ? `Observed ${formatDistanceToNow(new Date(node.resources.observedAt), { addSuffix: true })}`
        : 'No recent poll data'

const ServerCount = ({ node }: { node: NodeSummary }) => (
    <>
        {num(node.servers)}{' '}
        <span className='text-label font-normal'>
            server{node.servers === 1 ? '' : 's'}
        </span>
    </>
)

const NodesCard = ({ nodes }: { nodes: NodeSummary[] }) => (
    <Card>
        <CardHeader className='flex flex-row items-center justify-between space-y-0 p-5 pb-2'>
            <CardTitle className='text-base'>Nodes</CardTitle>
            <StatLabel as='span' className='text-xs'>
                {num(nodes.length)} host{nodes.length === 1 ? '' : 's'}
            </StatLabel>
        </CardHeader>
        <CardContent className='p-5 pt-2'>
            {nodes.length === 0 ? (
                <SimpleEmptyState
                    className='p-0 py-4'
                    icon={IconServerBolt}
                    title='No nodes yet'
                    description='Add a node to start provisioning servers.'
                    action={
                        <Link className={buttonVariants()} to='/admin/nodes'>
                            Add node
                        </Link>
                    }
                />
            ) : (
                <>
                    {/* Desktop: dense table */}
                    <div className='hidden @2xl:block'>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className='pl-0'>Node</TableHead>
                                    <TableHead className='w-20 text-center'>
                                        Servers
                                    </TableHead>
                                    <TableHead className='w-32'>CPU</TableHead>
                                    <TableHead className='w-[28%]'>Memory</TableHead>
                                    <TableHead className='w-[28%]'>Root disk</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {nodes.map(node => (
                                    <TableRow key={node.id}>
                                        <TableCell className='pl-0'>
                                            <div className='flex items-center gap-2'>
                                                <div className='font-semibold'>
                                                    {node.displayName}
                                                </div>
                                                <StatusBadge node={node} />
                                            </div>
                                            <div className='text-muted-foreground font-mono text-xs'>
                                                {node.fqdn}
                                            </div>
                                            <StatLabel className='mt-1 text-xs'>
                                                {snapshotAge(node)}
                                            </StatLabel>
                                        </TableCell>
                                        <TableCell className='text-center tabular-nums'>
                                            {num(node.servers)}
                                        </TableCell>
                                        <TableCell>
                                            {node.resources ? (
                                                <div>
                                                    <div className='text-sm font-semibold tabular-nums'>
                                                        {node.resources.cpu.percent}%
                                                    </div>
                                                    <StatLabel className='text-xs'>
                                                        {num(node.resources.cpu.count)} CPUs
                                                    </StatLabel>
                                                </div>
                                            ) : (
                                                <span className='text-muted-foreground text-sm'>
                                                    Unknown
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {node.resources ? (
                                                <Usage
                                                    usage={node.resources.memory}
                                                    label={`Memory used on ${node.displayName}`}
                                                    sub={`${bytes(node.memory.allocated)} committed`}
                                                />
                                            ) : (
                                                <span className='text-muted-foreground text-sm'>
                                                    Unknown · {bytes(node.memory.allocated)} committed
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className='pr-0'>
                                            {node.resources ? (
                                                <Usage
                                                    usage={node.resources.disk}
                                                    label={`Root disk used on ${node.displayName}`}
                                                />
                                            ) : (
                                                <span className='text-muted-foreground text-sm'>
                                                    Unknown
                                                </span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Mobile: stacked rows so the meter never gets squeezed.
                        The table's column headers carry the labelling on
                        desktop, so the meter has to be labelled explicitly
                        here. */}
                    <ItemGroup className='gap-3 @2xl:hidden'>
                        {nodes.map(node => (
                            <Item key={node.id} variant='muted' size='sm'>
                                <ItemContent className='min-w-0'>
                                    <div className='flex items-start justify-between gap-2'>
                                        <div className='min-w-0'>
                                            {/* ItemTitle is a `w-fit` flex row,
                                                so the ellipsis has to live on a
                                                child that can actually shrink. */}
                                            <ItemTitle className='w-full min-w-0'>
                                                <span className='truncate'>
                                                    {node.displayName}
                                                </span>
                                                <StatusBadge node={node} />
                                            </ItemTitle>
                                            {/* `block`/`text-nowrap` beat
                                                ItemDescription's default
                                                line-clamp-2 + text-balance,
                                                which otherwise silently defeat
                                                `truncate`. */}
                                            <ItemDescription className='block truncate text-nowrap font-mono text-xs'>
                                                {node.fqdn}
                                            </ItemDescription>
                                        </div>
                                        <div className='shrink-0 text-sm font-semibold tracking-tight tabular-nums'>
                                            <ServerCount node={node} />
                                        </div>
                                    </div>
                                    <StatLabel className='mt-2.5 mb-1.5 text-xs'>
                                        {snapshotAge(node)}
                                    </StatLabel>
                                    {node.resources ? (
                                        <div className='grid grid-cols-1 gap-3 @lg:grid-cols-2'>
                                            <div>
                                                <StatLabel className='mb-1.5 text-xs'>
                                                    CPU
                                                </StatLabel>
                                                <div className='text-sm font-semibold tabular-nums'>
                                                    {node.resources.cpu.percent}% ·{' '}
                                                    {num(node.resources.cpu.count)} CPUs
                                                </div>
                                            </div>
                                            <div>
                                                <StatLabel className='mb-1.5 text-xs'>
                                                    Memory
                                                </StatLabel>
                                                <Usage
                                                    usage={node.resources.memory}
                                                    label={`Memory used on ${node.displayName}`}
                                                    sub={`${bytes(node.memory.allocated)} committed`}
                                                />
                                            </div>
                                            <div className='@lg:col-span-2'>
                                                <StatLabel className='mb-1.5 text-xs'>
                                                    Root disk
                                                </StatLabel>
                                                <Usage
                                                    usage={node.resources.disk}
                                                    label={`Root disk used on ${node.displayName}`}
                                                />
                                            </div>
                                        </div>
                                    ) : (
                                        <div className='text-muted-foreground text-sm'>
                                            Resource usage unknown · {bytes(node.memory.allocated)} memory committed
                                        </div>
                                    )}
                                </ItemContent>
                            </Item>
                        ))}
                    </ItemGroup>
                </>
            )}
        </CardContent>
    </Card>
)

export default NodesCard
