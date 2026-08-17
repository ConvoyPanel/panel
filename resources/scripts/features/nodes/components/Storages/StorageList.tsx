import useStoragesModalStore from '@/features/nodes/hooks/use-storages-modal-store.ts'
import {
    type StorageCapacityView,
    storageCapacity,
    storageSummary,
} from '@/features/nodes/storages/capacity.ts'
import { NodeStorage } from '@/features/nodes/types.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import { cn } from '@/utils'
import byteSize from 'byte-size'
import { formatDistanceToNow } from 'date-fns'

import { Badge } from '@/components/ui/Badge.tsx'
import { buttonVariants } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/DropdownMenu'
import { Item, ItemActions, ItemContent, ItemGroup } from '@/components/ui/Item'
import { SegmentedProgressBar } from '@/components/ui/Progress'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'
import Actions from '@/components/ui/Table/Actions.tsx'
import { StatLabel } from '@/components/ui/Typography'

interface Props {
    storages: NodeStorage[]
}

const fmt = (bytes: number) => {
    const { value, unit } = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${value} ${unit}`
}

const StorageName = ({ storage }: { storage: NodeStorage }) => {
    const openModal = useOpenModal(useStoragesModalStore)

    return (
        <button
            className={cn(
                buttonVariants({ variant: 'link' }),
                // `self-start justify-start` because this is a <button>, not the
                // <Link> AnchorList uses: the button variant centres its content,
                // which left the name centred inside the stacked Item.
                'h-auto max-w-full min-w-0 justify-start self-start p-0 font-semibold'
            )}
            onClick={() => openModal('show', storage)}
        >
            <span className='truncate'>
                {storage.displayName ?? storage.name}
            </span>
        </button>
    )
}

/**
 * The sentence under the bar, which changes with the backend because the same
 * arithmetic means different things.
 *
 * On a thin or deduplicating store the ledger exceeding the disk is ordinary, so
 * the line explains why there is no breakdown. On a thick one the gap is space
 * nobody can account for, which is worth naming. Returns null when there is
 * nothing true left to say -- printing "0 B committed" is worse than silence.
 */
const qualifier = (
    storage: NodeStorage,
    view: StorageCapacityView
): string | null => {
    if (view.isThin) {
        const why =
            storage.pveType === 'pbs'
                ? 'deduplicated on the server'
                : 'thin, so more may be promised than written'

        return view.committed > 0
            ? `${fmt(view.committed)} committed — ${why}`
            : why.charAt(0).toUpperCase() + why.slice(1)
    }

    if (view.untracked) {
        return `${fmt(view.untracked)} untracked — on disk, but not Convoy's`
    }

    return view.committed > 0 ? `${fmt(view.committed)} committed` : null
}

/**
 * Who else is on this pool, in words.
 *
 * A "shared" badge says a pool is shared without saying what that costs the
 * reader: 20 TiB of free Ceph on four nodes is 20 TiB in total, not per node,
 * and an operator who does not know that will plan the same disk four times.
 */
const SharedWith = ({ storage }: { storage: NodeStorage }) =>
    storage.sharedWith.length > 0 ? (
        <StatLabel className='mt-1 block text-xs'>
            Shared with {storage.sharedWith.join(', ')} — this capacity is not
            this node&rsquo;s alone
        </StatLabel>
    ) : null

/**
 * How full a storage is, and the one sentence that qualifies the number.
 *
 * A store Proxmox could not read shows no bar at all: it reports zeroes when an
 * export is unmounted, and a 0% meter reads as "plenty of room" on exactly the
 * store that is unavailable.
 */
const Capacity = ({ storage }: { storage: NodeStorage }) => {
    const view = storageCapacity(storage)

    if (!view.known) {
        return (
            <>
                <Badge variant='outline'>Unreadable</Badge>
                <StatLabel className='mt-1 block text-xs'>
                    Proxmox could not read it — offline or not mounted
                </StatLabel>
                <SharedWith storage={storage} />
            </>
        )
    }

    return (
        <>
            <div className='font-mono text-xs tabular-nums'>
                {view.percent.toFixed(0)}% · {fmt(view.used)} /{' '}
                {fmt(view.total)}
                {view.freeForConvoy !== null && (
                    <>
                        {' · '}
                        <span className='font-semibold'>
                            {fmt(view.freeForConvoy)}
                        </span>{' '}
                        free
                    </>
                )}
            </div>
            <SegmentedProgressBar
                className='mt-1.5 h-2'
                segments={view.segments}
            />
            {qualifier(storage, view) && (
                <StatLabel className='mt-1 block text-xs'>
                    {qualifier(storage, view)}
                </StatLabel>
            )}
            <SharedWith storage={storage} />
        </>
    )
}

const StorageList = ({ storages }: Props) => {
    const openModal = useOpenModal(useStoragesModalStore)

    const menu = (storage: NodeStorage) => (
        <>
            <DropdownMenuItem onClick={() => openModal('show', storage)}>
                Usage
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => openModal('edit', storage)}>
                Edit
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                variant='destructive'
                onClick={() => openModal('delete', storage)}
            >
                Delete
            </DropdownMenuItem>
        </>
    )

    /*
     * How fresh the numbers are, said once for the list rather than per row --
     * every storage on a node is observed by the same poll, so repeating it
     * would be repeating one fact N times.
     */
    const observedAt = storages.find(storage => storage.observedAt)?.observedAt

    /*
     * Fullest first, unreadable last. The default order was insertion order,
     * which put whichever storage happened to be added most recently at the top
     * -- and floated the one storage with no figures above every real one.
     */
    const ordered = [...storages].sort((a, b) => {
        const left = storageCapacity(a)
        const right = storageCapacity(b)

        if (left.known !== right.known) return left.known ? -1 : 1

        return right.percent - left.percent
    })

    return (
        <Card className='overflow-hidden'>
            <div className='flex flex-wrap items-baseline justify-between gap-2 p-4'>
                <StatLabel className='text-xs'>
                    {storages.length} storage{storages.length === 1 ? '' : 's'}
                </StatLabel>
                {observedAt && (
                    <StatLabel className='text-xs'>
                        Observed{' '}
                        {formatDistanceToNow(new Date(observedAt), {
                            addSuffix: true,
                        })}
                    </StatLabel>
                )}
            </div>

            {/* Desktop: what it is on the left, how full it is on the right. */}
            <div className='hidden @3xl:block'>
                <Table>
                    <TableHeader>
                        <TableRow className='hover:bg-transparent'>
                            <TableHead className='pl-4'>Storage</TableHead>
                            <TableHead className='w-[22rem]'>
                                Capacity
                            </TableHead>
                            <TableHead className='w-12 pr-4' />
                        </TableRow>
                    </TableHeader>
                    <TableBody className='[&_tr:last-child]:border-0'>
                        {ordered.map(storage => (
                            <TableRow key={storage.id}>
                                <TableCell className='max-w-0 py-3 pl-4 align-top'>
                                    <StorageName storage={storage} />
                                    <StatLabel className='mt-0.5 block truncate text-xs text-nowrap'>
                                        {storageSummary(storage)}
                                    </StatLabel>
                                </TableCell>
                                <TableCell className='py-3 align-top'>
                                    <Capacity storage={storage} />
                                </TableCell>
                                <TableCell className='py-3 pr-4 align-top'>
                                    <Actions>{menu(storage)}</Actions>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {/* Narrow: the same two blocks stacked, as a divided list. */}
            <ItemGroup className='@3xl:hidden'>
                {ordered.map(storage => (
                    <Item
                        key={storage.id}
                        size='sm'
                        className='border-b-border rounded-none last:border-b-transparent'
                    >
                        <ItemContent className='min-w-0 gap-1'>
                            <StorageName storage={storage} />
                            <StatLabel className='block truncate text-xs text-nowrap'>
                                {storageSummary(storage)}
                            </StatLabel>
                            <div className='mt-1 w-full'>
                                <Capacity storage={storage} />
                            </div>
                        </ItemContent>
                        <ItemActions>
                            <Actions>{menu(storage)}</Actions>
                        </ItemActions>
                    </Item>
                ))}
            </ItemGroup>
        </Card>
    )
}

export default StorageList
