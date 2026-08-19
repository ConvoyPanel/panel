import AnchorProtocol from '@/features/anchors/components/AnchorProtocol.tsx'
import AnchorStatusCell from '@/features/anchors/components/AnchorStatusCell.tsx'
import type { Anchor } from '@/features/anchors/types.ts'
import { cn } from '@/utils'
import { IconPlugConnected } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import { ReactNode } from 'react'

import { buttonVariants } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/DropdownMenu'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import { Item, ItemActions, ItemContent, ItemGroup } from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'
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
    anchors: Anchor[]
    isLoading: boolean
    onEdit: (anchor: Anchor) => void
    onInstall: (anchor: Anchor) => void
    onDelete: (anchor: Anchor) => void
    emptyAction?: ReactNode
}

const plural = (count: number, noun: string) =>
    `${count} ${noun}${count === 1 ? '' : 's'}`

/**
 * What the anchor is, and what it carries. Nothing else: the line under a name
 * is read at a glance, and once it runs to routing and a capability list it
 * stops being scannable and starts being a paragraph.
 *
 * Routing and capabilities are still worth showing -- just not here. They
 * belong on the anchor's own screen, where there is room to label them.
 */
export const anchorSummary = (anchor: Anchor) =>
    [
        anchor.mode === 'relay' ? 'Relay' : 'Agent',
        anchor.agentsCount > 0 ? plural(anchor.agentsCount, 'agent') : null,
        anchor.nodesCount > 0 ? plural(anchor.nodesCount, 'node') : null,
    ]
        .filter(Boolean)
        .join(' · ')

/**
 * The release version, plus a marker only when the protocol this build speaks
 * doesn't overlap the panel's. Protocol is a different number in a different
 * scheme (an integer bumped per wire change), and printing it on every row asks
 * every reader to compare two ranges to learn "fine" -- so the healthy case
 * shows nothing and the broken one shows a badge whose tooltip has the numbers.
 */
const VersionCell = ({ anchor }: { anchor: Anchor }) =>
    anchor.version ? (
        <div className='flex flex-col items-start gap-1'>
            <span className='font-mono text-xs tabular-nums'>
                {anchor.version}
            </span>
            <AnchorProtocol anchor={anchor} quiet />
        </div>
    ) : (
        // The version column holds a version or nothing. What is missing is
        // already said, once, by the status cell beside it.
        <span className='text-muted-foreground' aria-hidden>
            —
        </span>
    )

const AnchorName = ({ anchor }: { anchor: Anchor }) => (
    <Link
        className={cn(
            buttonVariants({ variant: 'link' }),
            'h-auto max-w-full min-w-0 p-0 font-semibold'
        )}
        to='/admin/anchors/$anchorId'
        params={{ anchorId: String(anchor.id) }}
    >
        <span className='truncate'>{anchor.name}</span>
    </Link>
)

const AnchorList = ({
    anchors,
    isLoading,
    onEdit,
    onInstall,
    onDelete,
    emptyAction,
}: Props) => {
    const menu = (anchor: Anchor) => (
        <>
            <DropdownMenuItem onClick={() => onEdit(anchor)}>
                Edit
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => onInstall(anchor)}>
                {anchor.compatibility === 'unenrolled'
                    ? 'Install command'
                    : 'Reissue install command'}
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            {/* Always offered. Whether it can go through is the confirmation's
                question to answer, in a sentence, rather than the menu's to
                answer by quietly removing the item. */}
            <DropdownMenuItem
                variant='destructive'
                onClick={() => onDelete(anchor)}
            >
                Delete
            </DropdownMenuItem>
        </>
    )

    if (isLoading) {
        return (
            <Card className='divide-border divide-y'>
                {Array.from({ length: 3 }, (_, index) => (
                    <div key={index} className='flex flex-col gap-2 p-4'>
                        <Skeleton className='h-4 w-48' />
                        <Skeleton className='h-3 w-72' />
                    </div>
                ))}
            </Card>
        )
    }

    if (anchors.length === 0) {
        return (
            <Card>
                <SimpleEmptyState
                    icon={IconPlugConnected}
                    title='No anchors yet'
                    description='An anchor is a small daemon you install next to your Proxmox nodes. It carries console and VNC traffic between the panel and a node the panel cannot reach directly.'
                    action={emptyAction}
                />
            </Card>
        )
    }

    return (
        <Card className='overflow-hidden'>
            {/* Desktop: the four things worth a column, with everything else
                folded into the name cell's summary line. */}
            <div className='hidden @3xl:block'>
                <Table>
                    <TableHeader>
                        <TableRow className='hover:bg-transparent'>
                            <TableHead className='pl-4'>Anchor</TableHead>
                            <TableHead>Endpoint</TableHead>
                            <TableHead className='w-36'>Version</TableHead>
                            <TableHead className='w-64'>Status</TableHead>
                            <TableHead className='w-12 pr-4' />
                        </TableRow>
                    </TableHeader>
                    <TableBody className='[&_tr:last-child]:border-0'>
                        {anchors.map(anchor => (
                            <TableRow key={anchor.id}>
                                <TableCell className='max-w-0 py-3 pl-4 align-top'>
                                    <AnchorName anchor={anchor} />
                                    <StatLabel className='mt-0.5 block truncate text-xs text-nowrap'>
                                        {anchorSummary(anchor)}
                                    </StatLabel>
                                </TableCell>
                                <TableCell className='max-w-0 py-3 align-top'>
                                    <span className='text-muted-foreground block truncate font-mono text-xs'>
                                        {anchor.publicUrl}
                                    </span>
                                </TableCell>
                                <TableCell className='py-3 align-top'>
                                    <VersionCell anchor={anchor} />
                                </TableCell>
                                <TableCell className='py-3 align-top'>
                                    <AnchorStatusCell anchor={anchor} />
                                </TableCell>
                                <TableCell className='py-3 pr-4 align-top'>
                                    <Actions>{menu(anchor)}</Actions>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {/* Narrow: the same two lines, with the endpoint and status moved
                under them. The summary line is already a single string, so
                nothing has to be re-authored for this width. */}
            <ItemGroup className='@3xl:hidden'>
                {anchors.map(anchor => (
                    <Item
                        key={anchor.id}
                        size='sm'
                        // Item's border is transparent on all four sides;
                        // colouring only the bottom one turns the group into a
                        // divided list instead of a stack of boxed rows.
                        className='border-b-border rounded-none last:border-b-transparent'
                    >
                        <ItemContent className='min-w-0 gap-1'>
                            <AnchorName anchor={anchor} />
                            <StatLabel className='block truncate text-xs text-nowrap'>
                                {anchorSummary(anchor)}
                            </StatLabel>
                            <span className='text-muted-foreground block truncate font-mono text-xs'>
                                {anchor.publicUrl}
                            </span>
                            <AnchorStatusCell
                                anchor={anchor}
                                className='mt-1 text-sm'
                            />
                        </ItemContent>
                        <ItemActions>
                            <Actions>{menu(anchor)}</Actions>
                        </ItemActions>
                    </Item>
                ))}
            </ItemGroup>
        </Card>
    )
}

export default AnchorList
