import { IconServerBolt } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'

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

const memoryFigure = (node: NodeSummary) =>
    `${bytes(node.memory.allocated)} / ${bytes(node.memory.total)} · ${node.memory.percent}%`

const Meter = ({ percent, label }: { percent: number; label: string }) => (
    <LinearProgressBar
        value={Math.min(percent, 100)}
        aria-label={`${label}: ${percent}%`}
        indicatorClassName={meterIndicatorClass[capacityTone(percent)]}
    />
)

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
                                    <TableHead className='w-24 text-center'>
                                        Servers
                                    </TableHead>
                                    {/* The meter and its figure are separate
                                        columns so the table sizes the figure
                                        once for every row and all the bars end
                                        on the same edge. Sharing one cell makes
                                        each bar's width depend on how long its
                                        own figure happens to be. The width
                                        lives on the meter cell, not here — a
                                        width on a colSpan'd header is split
                                        across both columns. */}
                                    <TableHead colSpan={2}>
                                        Memory allocated
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {nodes.map(node => (
                                    <TableRow key={node.id}>
                                        <TableCell className='pl-0'>
                                            <div className='font-semibold'>
                                                {node.displayName}
                                            </div>
                                            <div className='text-muted-foreground font-mono text-xs'>
                                                {node.fqdn}
                                            </div>
                                        </TableCell>
                                        <TableCell className='text-center tabular-nums'>
                                            {num(node.servers)}
                                        </TableCell>
                                        <TableCell className='w-[36%]'>
                                            <Meter
                                                percent={node.memory.percent}
                                                label={`Memory allocated for ${node.displayName}`}
                                            />
                                        </TableCell>
                                        <TableCell className='pr-0 text-right'>
                                            <StatLabel
                                                as='span'
                                                className='text-xs whitespace-nowrap tabular-nums'
                                            >
                                                {memoryFigure(node)}
                                            </StatLabel>
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
                                        Memory allocated
                                    </StatLabel>
                                    <Meter
                                        percent={node.memory.percent}
                                        label={`Memory allocated for ${node.displayName}`}
                                    />
                                    <StatLabel className='mt-1.5 text-xs tabular-nums'>
                                        {memoryFigure(node)}
                                    </StatLabel>
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
