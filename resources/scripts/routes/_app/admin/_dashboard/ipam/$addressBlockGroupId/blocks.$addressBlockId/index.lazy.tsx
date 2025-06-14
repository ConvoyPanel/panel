import usePagination from '@/hooks/use-pagination.ts'
import { Address } from '@/types/address.ts'
import { Server } from '@/types/server.ts'
import { createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import useAddressesSWR from '@/api/admin/addressBlockGroups/addressBlocks/addresses/use-addresses-swr.ts'
import useAddressBlockSWR from '@/api/admin/addressBlockGroups/addressBlocks/use-address-block-swr.ts'

import GenerateAddressesButton from '@/components/interfaces/Admin/Ipam/AddressBlock/GenerateAddressesButton.tsx'

import { Badge } from '@/components/ui/Badge.tsx'
import { DataTable } from '@/components/ui/DataTable'
import DropdownMenuItem from '@/components/ui/DropdownMenu/DropdownMenuItem.tsx'
import { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'
import { useAddressModal } from '@/components/interfaces/Admin/Ipam/AddressBlock/use-address-modal.ts'
import EditAddressModal from '@/components/interfaces/Admin/Ipam/AddressBlock/EditAddressModal'
import DeleteAddressModal from '@/components/interfaces/Admin/Ipam/AddressBlock/DeleteAddressModal.tsx'

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/ipam/$addressBlockGroupId/blocks/$addressBlockId/'
)({
    component: BlockIndex,
})

function BlockIndex() {
    const { data: block } = useAddressBlockSWR()
    const pagination = usePagination()
    const { data, mutate } = useAddressesSWR(
        {
            page: pagination.page,
            filters: {
                ip: pagination.debouncedQuery,
            },
        },
        ['server']
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
                rightActions={<GenerateAddressesButton mutate={mutate} />}
                {...pagination}
            />
            <EditAddressModal mutate={mutate} />
            <DeleteAddressModal mutate={mutate} />
        </>
    )
}
