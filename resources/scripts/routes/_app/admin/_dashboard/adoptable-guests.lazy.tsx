import {
    type AdoptableGuest,
    useAdoptableGuests,
} from '@/features/adoption/api.ts'
import AdoptGuestModal from '@/features/adoption/components/AdoptGuestModal.tsx'
import { IconExclamationCircle, IconServer2 } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import byteSize from 'byte-size'
import { useState } from 'react'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge.tsx'
import { Button } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'
import { Heading } from '@/components/ui/Typography'

const formatBytes = (bytes: number) => {
    const size = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${size.value} ${size.unit}`
}

const AdoptableGuestsIndex = () => {
    const { data, isPending, isError, refetch } = useAdoptableGuests()
    const [adopting, setAdopting] = useState<AdoptableGuest | null>(null)

    const columns: ColumnDef<AdoptableGuest>[] = [
        {
            header: 'Name',
            accessorKey: 'name',
            enableHiding: false,
            meta: { skeletonWidth: '8rem' },
            cell: ({ row }) => row.original.name ?? '—',
        },
        {
            header: 'Node',
            accessorKey: 'nodeName',
            meta: { skeletonWidth: '5rem' },
        },
        {
            header: 'VMID',
            accessorKey: 'vmid',
            meta: { skeletonWidth: '3rem', align: 'center' },
            cell: ({ cell }) => (
                <Badge variant={'secondary'} className={'font-mono'}>
                    {cell.getValue<number>()}
                </Badge>
            ),
            maxSize: 60,
        },
        {
            header: 'State',
            accessorKey: 'status',
            meta: { skeletonWidth: '4rem' },
            cell: ({ cell }) => (
                <span className={'capitalize'}>{cell.getValue<string>()}</span>
            ),
        },
        {
            header: 'Resources',
            id: 'resources',
            meta: { skeletonWidth: '9rem' },
            cell: ({ row }) =>
                [
                    `${row.original.cpuCount} vCPU`,
                    formatBytes(row.original.memory),
                    formatBytes(row.original.diskSize),
                ].join(' · '),
        },
        {
            id: 'actions',
            header: '',
            enableHiding: false,
            meta: { align: 'right' },
            // A disabled button with the reason hidden in a tooltip reads as a
            // broken feature; the reason takes the button's place instead.
            cell: ({ row }) =>
                row.original.blockedReason ? (
                    <span className={'text-muted-foreground text-xs'}>
                        {row.original.blockedReason}
                    </span>
                ) : (
                    <Button
                        size={'sm'}
                        variant={'outline'}
                        onClick={() => setAdopting(row.original)}
                    >
                        Adopt
                    </Button>
                ),
        },
    ]

    return (
        <>
            <Heading>Unmanaged Guests</Heading>

            {data?.unreachable.map(scope => (
                <Alert key={scope.nodeId} variant={'destructive'}>
                    <IconExclamationCircle className={'size-4'} />
                    <AlertDescription>
                        {scope.nodeName} could not be asked for its guests, so
                        this list may be incomplete. {scope.reason}
                    </AlertDescription>
                </Alert>
            ))}

            <DataTable
                data={isPending ? undefined : (data?.guests ?? [])}
                columns={columns}
                searchable
                toolbar
                isError={isError}
                onRetry={refetch}
                emptyState={
                    <SimpleEmptyState
                        icon={IconServer2}
                        title={'No unmanaged guests'}
                        description={
                            'Every QEMU guest here is already a server.'
                        }
                    />
                }
                mobileRow={row => {
                    const guest = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'overflow-x-hidden'}>
                                <ItemTitle>
                                    {guest.name ?? `VMID ${guest.vmid}`}
                                </ItemTitle>
                                <ItemDescription className={'truncate'}>
                                    {guest.blockedReason ??
                                        `${guest.nodeName} · VMID ${guest.vmid} · ${guest.status}`}
                                </ItemDescription>
                            </ItemContent>
                            {!guest.blockedReason && (
                                <ItemActions>
                                    <Button
                                        size={'sm'}
                                        variant={'outline'}
                                        onClick={() => setAdopting(guest)}
                                    >
                                        Adopt
                                    </Button>
                                </ItemActions>
                            )}
                        </Item>
                    )
                }}
            />

            <AdoptGuestModal
                guest={adopting}
                onClose={() => setAdopting(null)}
            />
        </>
    )
}

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/adoptable-guests'
)({
    component: AdoptableGuestsIndex,
})
