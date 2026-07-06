import useDataTable from '@/hooks/use-data-table.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Address, PaginatedAddresses } from '@/types/address.ts'
import { Server } from '@/types/server.ts'
import { createLazyFileRoute, useParams } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import {
    useAddresses,
    addressQueries,
} from '@/features/ipam/blocks/addresses/api.ts'
import { useAddressBlock } from '@/features/ipam/blocks/api.ts'

import GenerateAddressesButton from '@/features/ipam/components/AddressBlock/GenerateAddressesButton.tsx'

import { Badge } from '@/components/ui/Badge.tsx'
import { DataTable } from '@/components/ui/DataTable'
import DropdownMenuItem from '@/components/ui/DropdownMenu/DropdownMenuItem.tsx'
import { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'
import { useAddressModal } from '@/features/ipam/hooks/use-address-modal.ts'
import EditAddressModal from '@/features/ipam/components/AddressBlock/EditAddressModal'
import DeleteAddressModal from '@/features/ipam/components/AddressBlock/DeleteAddressModal.tsx'

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
    const { data, isPlaceholderData } = useAddresses(queryParams, ['server'])
    const mutate = useQueryMutator<PaginatedAddresses>(
        addressQueries.list(
            Number(addressBlockGroupId),
            Number(addressBlockId),
            queryParams,
            ['server']
        ).queryKey
    )
    const openModal = useAddressModal(state => state.openModal)

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
        actionsColumn(({ row }) => {
            return (
                <>
                    <DropdownMenuItem onClick={() => openModal('edit', row.original)}>Edit</DropdownMenuItem>
                    <DropdownMenuItem onClick={() => openModal('delete', row.original)}>Delete</DropdownMenuItem>
                </>
            )
        }),
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
                rightActions={<GenerateAddressesButton mutate={mutate} />}
                {...tableProps}
            />
            <EditAddressModal mutate={mutate} />
            <DeleteAddressModal mutate={mutate} />
        </>
    )
}
