import {
    addressBlockGroupQueries,
    useAddressBlockGroups,
} from '@/features/ipam/api.ts'
import { addressCapacity, addressInUseLabel } from '@/features/ipam/capacity.ts'
import AddressCapacityMeter from '@/features/ipam/components/AddressCapacityMeter.tsx'
import CreateBlockGroupModal from '@/features/ipam/components/CreateBlockGroupModal.tsx'
import DeleteBlockGroupModal from '@/features/ipam/components/DeleteBlockGroupModal.tsx'
import EditBlockGroupModal from '@/features/ipam/components/EditBlockGroupModal.tsx'
import IpamSummaryCards from '@/features/ipam/components/IpamSummaryCards.tsx'
import useBlockGroupModalStore from '@/features/ipam/hooks/use-block-group-modal-store.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import {
    AddressBlockGroup,
    PaginatedAddressBlockGroups,
} from '@/types/address-block-group.ts'
import { cn } from '@/utils'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/DropdownMenu'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'
import Actions, { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/ipam/')({
    component: IpamIndex,
})

function IpamIndex() {
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData, isError, refetch } =
        useAddressBlockGroups(queryParams)
    const mutate = useQueryMutator<PaginatedAddressBlockGroups>(
        addressBlockGroupQueries.list(queryParams).queryKey
    )
    const openModal = useOpenModal(useBlockGroupModalStore)

    const renderActions = (group: AddressBlockGroup) => (
        <>
            <DropdownMenuItem onClick={() => openModal('edit', group)}>
                Edit
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                variant={'destructive'}
                onClick={() => openModal('delete', group)}
            >
                Delete
            </DropdownMenuItem>
        </>
    )

    const columns: ColumnDef<AddressBlockGroup>[] = [
        {
            header: 'IP Block Group',
            accessorKey: 'name',
            enableHiding: false,
            meta: {
                skeletonWidth: '8rem',
            },
            cell: ({ cell }) => (
                <div className={'min-w-0'}>
                    <Link
                        className={cn(
                            buttonVariants({ variant: 'link' }),
                            'h-auto px-0'
                        )}
                        to='/admin/ipam/$addressBlockGroupId'
                        params={{
                            addressBlockGroupId: String(cell.row.original.id),
                        }}
                    >
                        {cell.getValue<string>()}
                    </Link>
                    {cell.row.original.description && (
                        <p className={'text-muted-foreground truncate text-xs'}>
                            {cell.row.original.description}
                        </p>
                    )}
                </div>
            ),
        },
        {
            header: 'Blocks',
            accessorKey: 'addressBlocksCount',
            meta: {
                skeletonWidth: '1rem',
                align: 'center',
            },
            cell: ({ cell }) => (
                <span className={'font-mono tabular-nums'}>
                    {cell.getValue<number>()}
                </span>
            ),
        },
        {
            id: 'inUse',
            header: 'In use',
            meta: {
                skeletonWidth: '4rem',
                align: 'right',
            },
            cell: ({ row }) => (
                <span className={'font-mono tabular-nums'}>
                    {addressInUseLabel(addressCapacity(row.original.capacity))}
                </span>
            ),
        },
        {
            id: 'utilisation',
            header: 'Utilisation',
            // Declared so the column claims the room the reading needs. Left to auto-layout it
            // was squeezed to the header's width and the subline broke mid-phrase.
            size: 260,
            meta: {
                skeletonWidth: '8rem',
            },
            cell: ({ row }) => (
                <AddressCapacityMeter capacity={row.original.capacity} />
            ),
        },
        {
            header: 'Nodes',
            accessorKey: 'nodesCount',
            meta: {
                skeletonWidth: '1rem',
                align: 'center',
            },
            cell: ({ cell }) => (
                <span className={'font-mono tabular-nums'}>
                    {cell.getValue<number>()}
                </span>
            ),
        },
        actionsColumn<AddressBlockGroup>(({ row }) =>
            renderActions(row.original)
        ),
    ]

    return (
        <>
            <Heading>IPAM</Heading>
            <IpamSummaryCards />
            <DataTable
                data={data}
                columns={columns}
                paginated
                searchable
                toolbar
                isPlaceholderData={isPlaceholderData}
                isError={isError}
                onRetry={refetch}
                mobileRow={row => {
                    const group = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'min-w-0'}>
                                <ItemTitle className={'w-full min-w-0'}>
                                    {/* buttonVariants is inline-flex shrink-0, so
                                        `truncate` on the link itself neither shrinks
                                        nor ellipsises — the text has to truncate in
                                        an inner span. */}
                                    <Link
                                        className={cn(
                                            buttonVariants({ variant: 'link' }),
                                            'h-auto max-w-full min-w-0 shrink p-0'
                                        )}
                                        to='/admin/ipam/$addressBlockGroupId'
                                        params={{
                                            addressBlockGroupId: String(
                                                group.id
                                            ),
                                        }}
                                    >
                                        <span className={'truncate'}>
                                            {group.name}
                                        </span>
                                    </Link>
                                </ItemTitle>
                                <ItemDescription
                                    className={'block truncate text-nowrap'}
                                >
                                    {group.description || 'No description'}
                                </ItemDescription>
                                {/* The one place a bar reads better than a number:
                                    at this width the counts would wrap before the
                                    reader got to the ratio. */}
                                <AddressCapacityMeter
                                    capacity={group.capacity}
                                    className={'w-full pt-1'}
                                />
                            </ItemContent>
                            <ItemActions>
                                <Actions>{renderActions(group)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                rightActions={<CreateBlockGroupModal mutate={mutate} />}
                {...tableProps}
            />
            <EditBlockGroupModal mutate={mutate} />
            <DeleteBlockGroupModal mutate={mutate} />
        </>
    )
}
