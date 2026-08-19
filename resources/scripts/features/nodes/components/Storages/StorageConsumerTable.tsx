import type { StorageConsumer } from '@/features/nodes/storages/api.ts'
import byteSize from 'byte-size'

import { TablerIcon } from '@/lib/tabler.ts'

import { Button } from '@/components/ui/Button'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'
import { StatLabel } from '@/components/ui/Typography'

const fmt = (bytes: number) => {
    const { value, unit } = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${value} ${unit}`
}

interface Props {
    rows: StorageConsumer[]
    /** Column heading for the thing being listed: Server, Backup, ISO. */
    label: string
    /** What the second column holds — an owner, a server, nothing. */
    ownerLabel?: string
    emptyIcon: TablerIcon
    emptyTitle: string
    emptyDescription: string
    /** Absent when this kind cannot be removed from here. */
    onDelete?: (row: StorageConsumer) => void
}

/**
 * One kind of thing occupying a storage.
 *
 * Shared by all three tabs because the question is the same in each: what is it,
 * how much room does it take, and may I remove it. Rows arrive largest first
 * from the API rather than being sorted here, so the answer does not change
 * depending on which screen asked.
 */
const StorageConsumerTable = ({
    rows,
    label,
    ownerLabel,
    emptyIcon,
    emptyTitle,
    emptyDescription,
    onDelete,
}: Props) => {
    if (rows.length === 0) {
        return (
            <SimpleEmptyState
                className={'py-8'}
                icon={emptyIcon}
                title={emptyTitle}
                description={emptyDescription}
            />
        )
    }

    return (
        <Table>
            <TableHeader>
                <TableRow className={'hover:bg-transparent'}>
                    <TableHead className={'pl-4'}>{label}</TableHead>
                    {ownerLabel && (
                        <TableHead className={'w-56'}>{ownerLabel}</TableHead>
                    )}
                    <TableHead className={'w-28'}>Size</TableHead>
                    <TableHead className={'w-28 pr-4'} />
                </TableRow>
            </TableHeader>
            <TableBody className={'[&_tr:last-child]:border-0'}>
                {rows.map(row => (
                    <TableRow key={row.id}>
                        <TableCell className={'max-w-0 py-3 pl-4'}>
                            <div className={'truncate font-medium'}>
                                {row.name}
                            </div>
                            {row.detail && (
                                <StatLabel
                                    className={
                                        'mt-0.5 block truncate text-xs text-nowrap'
                                    }
                                >
                                    {row.detail}
                                </StatLabel>
                            )}
                        </TableCell>
                        {ownerLabel && (
                            <TableCell className={'text-muted-foreground py-3'}>
                                {row.owner ?? '—'}
                            </TableCell>
                        )}
                        <TableCell
                            className={'py-3 font-mono text-xs tabular-nums'}
                        >
                            {fmt(row.size)}
                        </TableCell>
                        <TableCell className={'py-3 pr-4 text-right'}>
                            {onDelete &&
                                (row.deletable ? (
                                    <Button
                                        size={'sm'}
                                        variant={'destructiveOutline'}
                                        onClick={() => onDelete(row)}
                                    >
                                        Delete
                                    </Button>
                                ) : (
                                    // Saying why beats a disabled button with no
                                    // explanation attached to it.
                                    <StatLabel className={'text-xs'}>
                                        Locked
                                    </StatLabel>
                                ))}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    )
}

export default StorageConsumerTable
