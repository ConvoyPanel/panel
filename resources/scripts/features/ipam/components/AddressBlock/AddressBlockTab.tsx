import {
    addressBlockQueries,
    useAddressBlocks,
} from '@/features/ipam/blocks/api.ts'
import { addressCapacity, addressInUseLabel } from '@/features/ipam/capacity.ts'
import CreateAddressBlockModal from '@/features/ipam/components/AddressBlock/CreateAddressBlockModal.tsx'
import DeleteAddressBlockModal from '@/features/ipam/components/AddressBlock/DeleteAddressBlockModal.tsx'
import EditAddressBlockModal from '@/features/ipam/components/AddressBlock/EditAddressBlockModal.tsx'
import AddressCapacityMeter from '@/features/ipam/components/AddressCapacityMeter.tsx'
import { useAddressBlockModal } from '@/features/ipam/hooks/use-address-block-modal.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId/index.lazy.tsx'
import { AddressBlock, PaginatedAddressBlocks } from '@/types/address-block.ts'
import { cn } from '@/utils'
import { Link } from '@tanstack/react-router'
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
import { TabsContent } from '@/components/ui/Tabs'

/** The block's CIDR — what an operator recognises it by, ahead of any name it was given. */
const cidr = (block: AddressBlock) =>
    `${block.baseIp}/${block.prefixLengthFrom}`

/**
 * What the block's geometry means, rather than the two numbers it is stored as.
 * `prefix_length_from`/`_to` are inputs; the size of the thing they describe is the answer.
 */
const handsOut = (block: AddressBlock) => {
    const { total } = addressCapacity(block.capacity)

    return total === null
        ? `/${block.prefixLengthTo} · on demand`
        : `/${block.prefixLengthTo} · ${total.toLocaleString()} units`
}

const AddressBlockTab = () => {
    const { addressBlockGroupId } = Route.useParams()
    const groupId = parseInt(addressBlockGroupId)
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData, isError, refetch } =
        useAddressBlocks(queryParams)
    const mutate = useQueryMutator<PaginatedAddressBlocks>(
        addressBlockQueries.list(groupId, queryParams).queryKey
    )
    const openModal = useOpenModal(useAddressBlockModal)

    const renderActions = (block: AddressBlock) => (
        <>
            <DropdownMenuItem asChild>
                <Link
                    to='/admin/ipam/$addressBlockGroupId/blocks/$addressBlockId'
                    params={{
                        addressBlockGroupId: String(block.addressBlockGroupId),
                        addressBlockId: String(block.id),
                    }}
                >
                    View
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => openModal('edit', block)}>
                Edit
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                variant={'destructive'}
                onClick={() => openModal('delete', block)}
            >
                Delete
            </DropdownMenuItem>
        </>
    )

    const columns: ColumnDef<AddressBlock>[] = [
        {
            header: 'IP Block',
            accessorKey: 'baseIp',
            enableHiding: false,
            meta: {
                skeletonWidth: '9rem',
            },
            cell: ({ row }) => (
                <div className={'min-w-0'}>
                    <Link
                        className={cn(
                            buttonVariants({ variant: 'link' }),
                            'h-auto px-0 font-mono'
                        )}
                        to='/admin/ipam/$addressBlockGroupId/blocks/$addressBlockId'
                        params={{
                            addressBlockGroupId: String(
                                row.original.addressBlockGroupId
                            ),
                            addressBlockId: String(row.original.id),
                        }}
                    >
                        {cidr(row.original)}
                    </Link>
                    {(row.original.name || row.original.description) && (
                        <p className={'text-muted-foreground truncate text-xs'}>
                            {row.original.name && row.original.description
                                ? `${row.original.name} — ${row.original.description}`
                                : (row.original.name ??
                                  row.original.description)}
                        </p>
                    )}
                </div>
            ),
        },
        {
            header: 'Gateway',
            accessorKey: 'gateway',
            meta: {
                skeletonWidth: '6rem',
            },
            cell: ({ cell }) => (
                <span className={'text-muted-foreground font-mono'}>
                    {cell.getValue<string | null>() ?? '—'}
                </span>
            ),
        },
        {
            id: 'handsOut',
            header: 'Hands out',
            meta: {
                skeletonWidth: '7rem',
            },
            cell: ({ row }) => (
                <span className={'text-muted-foreground font-mono'}>
                    {handsOut(row.original)}
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
        actionsColumn<AddressBlock>(({ row }) => renderActions(row.original)),
    ]

    return (
        <TabsContent value={'addressBlocks'}>
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
                    const block = row.original

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
                                            'h-auto max-w-full min-w-0 shrink p-0 font-mono'
                                        )}
                                        to='/admin/ipam/$addressBlockGroupId/blocks/$addressBlockId'
                                        params={{
                                            addressBlockGroupId: String(
                                                block.addressBlockGroupId
                                            ),
                                            addressBlockId: String(block.id),
                                        }}
                                    >
                                        <span className={'truncate'}>
                                            {cidr(block)}
                                        </span>
                                    </Link>
                                </ItemTitle>
                                <ItemDescription
                                    className={'block truncate text-nowrap'}
                                >
                                    {block.name ||
                                        block.description ||
                                        handsOut(block)}
                                </ItemDescription>
                                <AddressCapacityMeter
                                    capacity={block.capacity}
                                    className={'w-full pt-1'}
                                />
                            </ItemContent>
                            <ItemActions>
                                <Actions>{renderActions(block)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                rightActions={
                    <CreateAddressBlockModal
                        addressBlockGroupId={groupId}
                        mutate={mutate}
                    />
                }
                {...tableProps}
            />
            <EditAddressBlockModal
                addressBlockGroupId={groupId}
                mutate={mutate}
            />
            <DeleteAddressBlockModal
                addressBlockGroupId={groupId}
                mutate={mutate}
            />
        </TabsContent>
    )
}

export default AddressBlockTab
