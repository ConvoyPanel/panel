import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { LinearProgressBar } from '@/components/ui/Progress'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'

import {
    bytes,
    capacityTone,
    meterIndicatorClass,
    num,
} from './overview-helpers'

type NodeSummary = App.Data.Admin.Overview.NodeSummaryData

const memoryFigure = (node: NodeSummary) =>
    `${bytes(node.memory.allocated)} / ${bytes(node.memory.total)} · ${node.memory.percent}%`

const Meter = ({ percent }: { percent: number }) => (
    <LinearProgressBar
        value={Math.min(percent, 100)}
        indicatorClassName={meterIndicatorClass[capacityTone(percent)]}
    />
)

const NodesCard = ({ nodes }: { nodes: NodeSummary[] }) => (
    <Card>
        <CardHeader className='flex flex-row items-center justify-between space-y-0 p-5 pb-2'>
            <CardTitle className='text-base'>Nodes</CardTitle>
            <span className='text-muted-foreground text-xs'>
                {num(nodes.length)} host{nodes.length === 1 ? '' : 's'}
            </span>
        </CardHeader>
        <CardContent className='p-5 pt-2'>
            {nodes.length === 0 ? (
                <p className='text-muted-foreground py-2 text-sm'>
                    No nodes yet.
                </p>
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
                                    <TableHead className='w-[46%] pr-0'>
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
                                        <TableCell className='pr-0'>
                                            <div className='grid grid-cols-[1fr_auto] items-center gap-3'>
                                                <Meter
                                                    percent={
                                                        node.memory.percent
                                                    }
                                                />
                                                <span className='text-muted-foreground text-xs whitespace-nowrap tabular-nums'>
                                                    {memoryFigure(node)}
                                                </span>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Mobile: stacked cards so the meter never gets squeezed */}
                    <div className='@2xl:hidden'>
                        {nodes.map(node => (
                            <div
                                key={node.id}
                                className='border-b py-3.5 first:pt-0 last:border-0 last:pb-0'
                            >
                                <div className='mb-2.5 flex items-start justify-between gap-2'>
                                    <div className='min-w-0'>
                                        <div className='font-semibold'>
                                            {node.displayName}
                                        </div>
                                        <div className='text-muted-foreground truncate font-mono text-xs'>
                                            {node.fqdn}
                                        </div>
                                    </div>
                                    <div className='shrink-0 text-sm font-semibold tracking-tight tabular-nums'>
                                        {num(node.servers)}{' '}
                                        <span className='text-muted-foreground font-normal'>
                                            servers
                                        </span>
                                    </div>
                                </div>
                                <div className='text-muted-foreground mb-1.5 text-xs'>
                                    Memory allocated
                                </div>
                                <Meter percent={node.memory.percent} />
                                <div className='text-muted-foreground mt-1.5 text-xs tabular-nums'>
                                    {memoryFigure(node)}
                                </div>
                            </div>
                        ))}
                    </div>
                </>
            )}
        </CardContent>
    </Card>
)

export default NodesCard
