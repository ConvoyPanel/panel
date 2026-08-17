import {
    storageCapacity,
    storageSummary,
} from '@/features/nodes/storages/capacity.ts'
import { NodeStorage } from '@/features/nodes/types.ts'
import { cn } from '@/utils'
import { Link } from '@tanstack/react-router'
import byteSize from 'byte-size'

import { Badge } from '@/components/ui/Badge.tsx'
import { buttonVariants } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { Item, ItemContent, ItemGroup, ItemTitle } from '@/components/ui/Item'
import { SegmentedProgressBar } from '@/components/ui/Progress'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'
import { StatLabel } from '@/components/ui/Typography'

interface Props {
    storages: NodeStorage[]
}

const fmt = (bytes: number) => {
    const { value, unit } = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${value} ${unit}`
}

/**
 * Which hosts reach this storage.
 *
 * The column the page exists for: it is the only thing distinguishing one pool
 * mounted by four hosts from four separate disks that happen to share the name
 * `local-lvm`. Never truncated to a count for that reason.
 */
const Nodes = ({ storage }: { storage: NodeStorage }) =>
    storage.sharedWith.length > 0 ? (
        <span className='flex flex-wrap gap-x-1 gap-y-0.5 text-xs'>
            {storage.sharedWith.map((node, index) => (
                <span key={node.id}>
                    {/* Linked to that node's own storages tab, which is where
                        this storage can actually be acted on -- the inventory
                        answers "where is it", the tab answers "change it". */}
                    <Link
                        className={cn(
                            buttonVariants({ variant: 'link' }),
                            'h-auto p-0 font-mono text-xs font-normal'
                        )}
                        to='/admin/nodes/$nodeId/storages'
                        params={{ nodeId: String(node.id) }}
                    >
                        {node.name}
                    </Link>
                    {index < storage.sharedWith.length - 1 && ','}
                </span>
            ))}
        </span>
    ) : (
        <StatLabel className='text-xs'>Not attached to a node</StatLabel>
    )

const Capacity = ({ storage }: { storage: NodeStorage }) => {
    const view = storageCapacity(storage)

    if (!view.known) {
        return (
            <>
                <Badge variant='outline'>Unknown</Badge>
                <StatLabel className='mt-1 block text-xs'>
                    Convoy has not recorded capacity for this storage
                </StatLabel>
            </>
        )
    }

    return (
        <>
            <div className='font-mono text-xs tabular-nums'>
                {view.percent.toFixed(0)}% · {fmt(view.used)} /{' '}
                {fmt(view.total)}
            </div>
            <SegmentedProgressBar
                className='mt-1.5 h-2'
                segments={view.segments}
            />
        </>
    )
}

/**
 * Every storage across every node.
 *
 * Fullest first, unrecorded last -- the same order the node's own list uses, so
 * moving between the two does not reshuffle what the reader was looking at.
 */
const StorageInventory = ({ storages }: Props) => {
    const ordered = [...storages].sort((a, b) => {
        const left = storageCapacity(a)
        const right = storageCapacity(b)

        if (left.known !== right.known) return left.known ? -1 : 1

        return right.percent - left.percent
    })

    return (
        <Card className='overflow-hidden'>
            <div className='p-4'>
                <StatLabel className='text-xs'>
                    {storages.length} storage{storages.length === 1 ? '' : 's'}{' '}
                    across the fleet
                </StatLabel>
            </div>

            <div className='hidden @3xl:block'>
                <Table>
                    <TableHeader>
                        <TableRow className='hover:bg-transparent'>
                            <TableHead className='pl-4'>Storage</TableHead>
                            <TableHead className='w-[18rem]'>Nodes</TableHead>
                            <TableHead className='w-[18rem] pr-4'>
                                Capacity
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody className='[&_tr:last-child]:border-0'>
                        {ordered.map(storage => (
                            <TableRow key={storage.id}>
                                <TableCell className='max-w-0 py-3 pl-4 align-top'>
                                    <div className='truncate font-semibold'>
                                        {storage.displayName ?? storage.name}
                                    </div>
                                    <StatLabel className='mt-0.5 block truncate text-xs text-nowrap'>
                                        {storageSummary(storage)}
                                    </StatLabel>
                                </TableCell>
                                <TableCell className='py-3 align-top'>
                                    <Nodes storage={storage} />
                                </TableCell>
                                <TableCell className='py-3 pr-4 align-top'>
                                    <Capacity storage={storage} />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <ItemGroup className='@3xl:hidden'>
                {ordered.map(storage => (
                    <Item
                        key={storage.id}
                        size='sm'
                        className='border-b-border rounded-none last:border-b-transparent'
                    >
                        <ItemContent className='min-w-0 gap-1'>
                            <ItemTitle className='w-full min-w-0'>
                                <span className='truncate'>
                                    {storage.displayName ?? storage.name}
                                </span>
                            </ItemTitle>
                            <StatLabel className='block truncate text-xs text-nowrap'>
                                {storageSummary(storage)}
                            </StatLabel>
                            <Nodes storage={storage} />
                            <div className='mt-1 w-full'>
                                <Capacity storage={storage} />
                            </div>
                        </ItemContent>
                    </Item>
                ))}
            </ItemGroup>
        </Card>
    )
}

export default StorageInventory
