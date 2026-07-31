import {
    addressQueries,
    reserveAddress,
    unreserveAddress,
    useAddresses,
} from '@/features/ipam/blocks/addresses/api.ts'
import { useAddressBlock } from '@/features/ipam/blocks/api.ts'
import DeleteAddressModal from '@/features/ipam/components/AddressBlock/DeleteAddressModal.tsx'
import EditAddressModal from '@/features/ipam/components/AddressBlock/EditAddressModal'
import GenerateAddressesButton from '@/features/ipam/components/AddressBlock/GenerateAddressesButton.tsx'
import { useAddressModal } from '@/features/ipam/hooks/use-address-modal.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import {
    Address,
    AddressState,
    AddressStateReason,
    PaginatedAddresses,
} from '@/types/address.ts'
import { Server } from '@/types/server.ts'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute, useParams } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import { Badge } from '@/components/ui/Badge.tsx'
import { DataTable } from '@/components/ui/DataTable'
import DropdownMenuItem from '@/components/ui/DropdownMenu/DropdownMenuItem.tsx'
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

const isSystemReserved = (address: Address) =>
    address.state === AddressState.Reserved &&
    address.stateReason === AddressStateReason.System

/**
 * Spell out *why* an address is reserved. A system reservation (network, broadcast, gateway) can't
 * be freed, so saying only "reserved" leaves an operator hunting for a missing Unreserve action.
 */
const stateLabel = (address: Address) =>
    isSystemReserved(address) ? 'reserved · system' : address.state

const AddressStateBadge = ({ address }: { address: Address }) => (
    <Badge
        variant={
            address.state === AddressState.Reserved ? 'outline' : 'secondary'
        }
        className={'capitalize'}
    >
        {stateLabel(address)}
    </Badge>
)

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
    const { data, isPlaceholderData, isError, refetch } = useAddresses(
        queryParams,
        ['server']
    )
    const mutate = useQueryMutator<PaginatedAddresses>(
        addressQueries.list(
            Number(addressBlockGroupId),
            Number(addressBlockId),
            queryParams,
            ['server']
        ).queryKey
    )
    const openModal = useOpenModal(useAddressModal)

    const { mutate: toggleReservation } = useMutation({
        mutationFn: async (address: Address) => {
            const updated =
                address.state === AddressState.Reserved
                    ? await unreserveAddress(
                          Number(addressBlockGroupId),
                          Number(addressBlockId),
                          address.id
                      )
                    : await reserveAddress(
                          Number(addressBlockGroupId),
                          Number(addressBlockId),
                          address.id
                      )

            await mutate(data => {
                if (!data) return
                return {
                    ...data,
                    items: data.items.map(item =>
                        item.id === updated.id ? updated : item
                    ),
                }
            }, false)

            toast.add({
                title:
                    updated.state === AddressState.Reserved
                        ? 'Address reserved'
                        : 'Address unreserved',
                type: 'success',
            })
        },
        onError: () =>
            toast.add({ title: 'Failed to update reservation', type: 'error' }),
    })

    const renderActions = (address: Address) => (
        <>
            <DropdownMenuItem onClick={() => openModal('edit', address)}>
                Edit
            </DropdownMenuItem>
            {address.state === AddressState.Available && (
                <DropdownMenuItem onClick={() => toggleReservation(address)}>
                    Reserve
                </DropdownMenuItem>
            )}
            {address.state === AddressState.Reserved &&
                !isSystemReserved(address) && (
                    <DropdownMenuItem
                        onClick={() => toggleReservation(address)}
                    >
                        Unreserve
                    </DropdownMenuItem>
                )}
            <DropdownMenuItem
                variant={'destructive'}
                onClick={() => openModal('delete', address)}
            >
                Delete
            </DropdownMenuItem>
        </>
    )

    const columns: ColumnDef<Address>[] = [
        {
            header: 'IP',
            accessorKey: 'ip',
            meta: {
                skeletonWidth: '10rem',
            },
            cell: ({ cell }) => (
                <Badge variant={'secondary'} className={'font-mono'}>
                    {cell.getValue<string>() +
                        '/' +
                        cell.row.original.prefixLength}
                </Badge>
            ),
        },
        {
            header: 'Mac Address',
            accessorKey: 'macAddress',
            meta: {
                skeletonWidth: '5rem',
            },
            cell: ({ cell }) => {
                const macAddress = cell.getValue<string | null>()
                return (
                    macAddress && (
                        <Badge variant={'secondary'} className={'font-mono'}>
                            {macAddress}
                        </Badge>
                    )
                )
            },
        },
        {
            header: 'State',
            accessorKey: 'state',
            meta: {
                skeletonWidth: '5rem',
            },
            cell: ({ row }) => <AddressStateBadge address={row.original} />,
        },
        {
            header: 'Server',
            accessorKey: 'server',
            meta: {
                skeletonWidth: '5rem',
            },
            cell: ({ cell }) => {
                const server = cell.getValue<Server | null>()

                return (
                    server && (
                        <Badge
                            variant={'secondary'}
                            className={'truncate font-mono'}
                        >
                            {server.name}
                        </Badge>
                    )
                )
            },
        },
        actionsColumn<Address>(({ row }) => renderActions(row.original)),
    ]

    return (
        <>
            <Heading className={'max-w-xl truncate'}>
                {block?.name ?? `${block?.baseIp}/${block?.prefixLengthFrom}`}
            </Heading>
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
                    const address = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'min-w-0'}>
                                <ItemTitle className={'w-full min-w-0'}>
                                    <span className={'truncate font-mono'}>
                                        {address.ip}/{address.prefixLength}
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
                                <div className={'flex flex-wrap gap-2'}>
                                    <AddressStateBadge address={address} />
                                    {address.server && (
                                        <Badge
                                            variant={'secondary'}
                                            className={
                                                'max-w-full truncate font-mono'
                                            }
                                        >
                                            {address.server.name}
                                        </Badge>
                                    )}
                                </div>
                            </ItemContent>
                            <ItemActions>
                                <Actions>{renderActions(address)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                rightActions={<GenerateAddressesButton mutate={mutate} />}
                {...tableProps}
            />
            <EditAddressModal mutate={mutate} />
            <DeleteAddressModal mutate={mutate} />
        </>
    )
}
