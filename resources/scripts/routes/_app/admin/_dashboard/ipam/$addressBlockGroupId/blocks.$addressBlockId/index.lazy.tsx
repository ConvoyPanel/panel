import {
    addressQueries,
    reserveAddress,
    unreserveAddress,
    useAddresses,
} from '@/features/ipam/blocks/addresses/api.ts'
import {
    addressBlockQueries,
    useAddressBlock,
} from '@/features/ipam/blocks/api.ts'
import AddressBlockCard from '@/features/ipam/components/AddressBlock/AddressBlockCard.tsx'
import AddressBulkActions from '@/features/ipam/components/AddressBlock/AddressBulkActions.tsx'
import DeleteAddressModal from '@/features/ipam/components/AddressBlock/DeleteAddressModal.tsx'
import EditAddressBlockModal from '@/features/ipam/components/AddressBlock/EditAddressBlockModal.tsx'
import EditAddressModal from '@/features/ipam/components/AddressBlock/EditAddressModal'
import AddressStateLabel, {
    addressStateKind,
} from '@/features/ipam/components/AddressStateLabel.tsx'
import { useAddressModal } from '@/features/ipam/hooks/use-address-modal.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Address, AddressState, PaginatedAddresses } from '@/types/address.ts'
import { DataTableFilterField } from '@/types/data-table.ts'
import { cn } from '@/utils'
import { useMutation } from '@tanstack/react-query'
import { Link, createLazyFileRoute, useParams } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import { queryClient } from '@/lib/query-client.ts'

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
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

/**
 * The four states the filter offers, matching what the API counts and what the row shows. `system`
 * is a reserved row the panel made and nobody can release, so it is its own option rather than
 * something the reader has to notice inside "reserved".
 */
const STATE_FILTER: DataTableFilterField<Address> = {
    id: 'state',
    label: 'State',
    options: [
        { label: 'Available', value: 'available' },
        { label: 'Assigned', value: 'assigned' },
        { label: 'Reserved', value: 'reserved' },
        { label: 'System', value: 'system' },
    ],
}

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/ipam/$addressBlockGroupId/blocks/$addressBlockId/'
)({
    component: BlockIndex,
})

function BlockIndex() {
    const { data: block } = useAddressBlock()
    const { queryParams, tableProps } = useDataTable({ searchKey: 'ip' })
    const { addressBlockGroupId, addressBlockId } = useParams({
        strict: false,
    }) as {
        addressBlockGroupId: number
        addressBlockId: number
    }
    const groupId = Number(addressBlockGroupId)
    const blockId = Number(addressBlockId)
    const { data, isPlaceholderData, isError, refetch } = useAddresses(
        queryParams,
        ['server']
    )
    const mutate = useQueryMutator<PaginatedAddresses>(
        addressQueries.list(groupId, blockId, queryParams, ['server']).queryKey
    )
    const openModal = useOpenModal(useAddressModal)

    const { mutate: toggleReservation } = useMutation({
        mutationFn: async (address: Address) => {
            const updated =
                address.state === AddressState.Reserved
                    ? await unreserveAddress(groupId, blockId, address.id)
                    : await reserveAddress(groupId, blockId, address.id)

            await mutate(data => {
                if (!data) return
                return {
                    ...data,
                    items: data.items.map(item =>
                        item.id === updated.id ? updated : item
                    ),
                }
            }, false)

            // The header meter reads the block's own counts, which just moved.
            await queryClient.invalidateQueries({
                queryKey: addressBlockQueries.detail(groupId, blockId).queryKey,
            })

            toast.add({
                title:
                    updated.state === AddressState.Reserved
                        ? 'Address reserved'
                        : 'Address released',
                type: 'success',
            })
        },
        onError: () =>
            toast.add({ title: 'Failed to update reservation', type: 'error' }),
    })

    const renderActions = (address: Address) => {
        const kind = addressStateKind(address)

        return (
            <>
                <DropdownMenuItem onClick={() => openModal('edit', address)}>
                    Edit
                </DropdownMenuItem>
                {kind === 'available' && (
                    <DropdownMenuItem
                        onClick={() => toggleReservation(address)}
                    >
                        Reserve
                    </DropdownMenuItem>
                )}
                {kind === 'reserved' && (
                    <DropdownMenuItem
                        onClick={() => toggleReservation(address)}
                    >
                        Release
                    </DropdownMenuItem>
                )}
                {/* The destructive item sits below a rule, the way it does in every other menu in
                    IPAM. It used to follow Reserve with nothing between them. */}
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    variant={'destructive'}
                    onClick={() => openModal('delete', address)}
                >
                    Delete
                </DropdownMenuItem>
            </>
        )
    }

    const columns: ColumnDef<Address>[] = [
        {
            header: 'IP',
            accessorKey: 'ip',
            enableHiding: false,
            meta: {
                skeletonWidth: '8rem',
            },
            /* Plain mono text, not a Badge. The prefix length is stated once in the header rather
               than repeated on all 253 rows, which leaves the address as the only mono-weighted
               thing in the row — so the eye lands on the identity. */
            cell: ({ cell }) => (
                <span className={'font-mono font-medium'}>
                    {cell.getValue<string>()}
                </span>
            ),
        },
        {
            header: 'State',
            accessorKey: 'state',
            meta: {
                skeletonWidth: '5rem',
            },
            cell: ({ row }) => (
                <AddressStateLabel kind={addressStateKind(row.original)} />
            ),
        },
        {
            header: 'Server',
            accessorKey: 'server',
            meta: {
                skeletonWidth: '6rem',
            },
            cell: ({ row }) => {
                const server = row.original.server

                if (!server) {
                    return <span className={'text-muted-foreground'}>—</span>
                }

                return (
                    <Link
                        className={cn(
                            buttonVariants({ variant: 'link' }),
                            'h-auto px-0'
                        )}
                        to={`/admin/servers/${server.id}` as string}
                    >
                        {server.name}
                    </Link>
                )
            },
        },
        {
            header: 'MAC address',
            accessorKey: 'macAddress',
            meta: {
                skeletonWidth: '7rem',
            },
            cell: ({ cell }) => (
                <span className={'text-muted-foreground font-mono text-xs'}>
                    {cell.getValue<string | null>() ?? '—'}
                </span>
            ),
        },
        actionsColumn<Address>(({ row }) => renderActions(row.original)),
    ]

    return (
        <>
            <Heading className={'max-w-xl truncate font-mono'}>
                {block ? `${block.baseIp}/${block.prefixLengthFrom}` : ''}
            </Heading>
            <AddressBlockCard block={block} mutate={mutate} />
            <DataTable
                data={data}
                columns={columns}
                paginated
                searchable
                toolbar
                filterFields={[STATE_FILTER]}
                enableRowSelection
                bulkActions={addresses => (
                    <AddressBulkActions
                        addresses={addresses}
                        addressBlockGroupId={groupId}
                        addressBlockId={blockId}
                        mutate={mutate}
                    />
                )}
                isPlaceholderData={isPlaceholderData}
                isError={isError}
                onRetry={refetch}
                mobileRow={row => {
                    const address = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'min-w-0'}>
                                <ItemTitle className={'w-full min-w-0'}>
                                    <span className={'truncate font-mono'}>
                                        {address.ip}
                                    </span>
                                </ItemTitle>
                                {address.macAddress && (
                                    <ItemDescription
                                        className={
                                            'block truncate font-mono text-nowrap'
                                        }
                                    >
                                        {address.macAddress}
                                    </ItemDescription>
                                )}
                                <div className={'flex flex-wrap gap-3'}>
                                    <AddressStateLabel
                                        kind={addressStateKind(address)}
                                    />
                                    {address.server && (
                                        <Link
                                            className={cn(
                                                buttonVariants({
                                                    variant: 'link',
                                                }),
                                                'h-auto max-w-full min-w-0 shrink p-0'
                                            )}
                                            to={
                                                `/admin/servers/${address.server.id}` as string
                                            }
                                        >
                                            <span className={'truncate'}>
                                                {address.server.name}
                                            </span>
                                        </Link>
                                    )}
                                </div>
                            </ItemContent>
                            <ItemActions>
                                <Actions>{renderActions(address)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                {...tableProps}
            />
            <EditAddressModal mutate={mutate} />
            <DeleteAddressModal mutate={mutate} />
            {/* The block card's Edit opens this. Delete stays on the pool's list: removing the
                block from the page that is about it would strand the reader on a dead route. */}
            <EditAddressBlockModal
                addressBlockGroupId={groupId}
                mutate={async () => {
                    await queryClient.invalidateQueries({
                        queryKey: addressBlockQueries.detail(groupId, blockId)
                            .queryKey,
                    })
                }}
            />
        </>
    )
}
