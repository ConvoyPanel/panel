import { IconServerBolt } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'

import { buttonVariants } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemContent,
    ItemDescription,
    ItemTitle,
    OverflowItemGroup,
} from '@/components/ui/Item'
import { LinearProgressBar } from '@/components/ui/Progress'
import { StatLabel } from '@/components/ui/Typography'

import {
    bytes,
    capacityTone,
    meterIndicatorClass,
    num,
} from './overview-helpers'

type NodeSummary = App.Data.Admin.Overview.NodeSummaryData

/** Keeps the card from growing with the fleet; overflow moves into the sheet. */
const MAX_VISIBLE = 5

const memoryFigure = (node: NodeSummary) =>
    `${bytes(node.memory.allocated)} / ${bytes(node.memory.total)} · ${node.memory.percent}%`

const NodeRow = ({ node }: { node: NodeSummary }) => (
    <Item variant='muted' size='sm' className='items-start gap-3'>
        <ItemContent className='min-w-0'>
            <div className='flex items-start justify-between gap-2'>
                <div className='min-w-0'>
                    {/* ItemTitle is a `w-fit` flex row, so the ellipsis has to
                        live on a child that can actually shrink. */}
                    <ItemTitle className='w-full min-w-0'>
                        <span className='truncate'>{node.displayName}</span>
                    </ItemTitle>
                    {/* `block` and `text-nowrap` are both load-bearing on top
                        of `truncate`: ItemDescription defaults to
                        `line-clamp-2` (display: -webkit-box) and
                        `text-balance`. `text-wrap` is a longhand of
                        `white-space`, so text-balance quietly beats truncate's
                        `nowrap` and the FQDN wraps with an ellipsis that never
                        shows. */}
                    <ItemDescription className='block truncate text-nowrap font-mono text-xs'>
                        {node.fqdn}
                    </ItemDescription>
                </div>
                <div className='shrink-0 text-sm font-semibold tracking-tight tabular-nums'>
                    {num(node.servers)}{' '}
                    <span className='text-label font-normal'>
                        server{node.servers === 1 ? '' : 's'}
                    </span>
                </div>
            </div>
            {/* The meter and its figure sit side by side once the row is wide
                enough; stacking below that keeps the bar from being squeezed
                to a few pixels. */}
            <div className='mt-2.5 grid gap-1.5 @2xl:grid-cols-[1fr_auto] @2xl:items-center @2xl:gap-3'>
                <LinearProgressBar
                    value={Math.min(node.memory.percent, 100)}
                    aria-label={`Memory allocated for ${node.displayName}: ${node.memory.percent}%`}
                    indicatorClassName={
                        meterIndicatorClass[capacityTone(node.memory.percent)]
                    }
                />
                <StatLabel
                    as='span'
                    className='text-xs whitespace-nowrap tabular-nums'
                >
                    {memoryFigure(node)}
                </StatLabel>
            </div>
        </ItemContent>
    </Item>
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
                <OverflowItemGroup
                    title='Nodes'
                    max={MAX_VISIBLE}
                    rows={nodes.map(node => (
                        <NodeRow key={node.id} node={node} />
                    ))}
                />
            )}
        </CardContent>
    </Card>
)

export default NodesCard
