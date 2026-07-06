import useDataTable from '@/hooks/use-data-table.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId/index.lazy.tsx'
import {
    AddressBlock,
    PaginatedAddressBlocks,
} from '@/types/address-block.ts'
import { AddressVersion } from '@/types/address.ts'
import { Link } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import {
    useAddressBlocks,
    addressBlockQueries,
} from '@/features/ipam/blocks/api.ts'

import CreateAddressBlockModal from '@/components/interfaces/Admin/Ipam/AddressBlock/CreateAddressBlockModal.tsx'
import DeleteAddressBlockModal from '@/components/interfaces/Admin/Ipam/AddressBlock/DeleteAddressBlockModal.tsx'
import EditAddressBlockModal from '@/components/interfaces/Admin/Ipam/AddressBlock/EditAddressBlockModal.tsx'
import { useAddressBlockModal } from '@/components/interfaces/Admin/Ipam/AddressBlock/use-address-block-modal.ts'

import { Badge } from '@/components/ui/Badge.tsx'
import { DataTable } from '@/components/ui/DataTable'
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/DropdownMenu'
import { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { TabsContent } from '@/components/ui/Tabs'

const AddressBlockTab = () => {
    const { addressBlockGroupId } = Route.useParams()
    const groupId = parseInt(addressBlockGroupId)
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData } = useAddressBlocks(queryParams)
    const mutate = useQueryMutator<PaginatedAddressBlocks>(
        addressBlockQueries.list(groupId, queryParams).queryKey
    )

    const columns: ColumnDef<AddressBlock>[] = [
        {
            header: 'IP Block',
            accessorKey: 'name',
            meta: {
                skeletonWidth: '5rem',
            },
        },
        {
            header: 'Description',
            accessorKey: 'description',
            meta: {
                skeletonWidth: '10rem',
            },
        },
        {
            header: 'Version',
            accessorKey: 'version',
            meta: {
                skeletonWidth: '1rem',
                align: 'center',
            },
            cell: ({ cell }) =>
                cell.getValue<AddressVersion>() === AddressVersion.IPv4
                    ? 'IPv4'
                    : 'IPv6',
        },
        {
            header: 'IP',
            accessorKey: 'baseIp',
            meta: {
                skeletonWidth: '10rem',
            },
            cell: ({ cell, row }) => (
                <Badge variant={'secondary'} className={'font-mono'}>
                    {cell.getValue<string>() +
                        '/' +
                        row.original.prefixLengthFrom}
                </Badge>
            ),
        },
        actionsColumn(({ row }) => {
            const openModal = useAddressBlockModal(state => state.openModal)

            return (
                <>
                    <DropdownMenuItem asChild>
                        <Link
                            to='/admin/ipam/$addressBlockGroupId/blocks/$addressBlockId'
                            params={{
                                addressBlockGroupId: String(row.original.addressBlockGroupId),
                                addressBlockId: String(row.original.id),
                            }}
                        >
                            View
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        onClick={() => openModal('edit', row.original)}
                    >
                        Edit
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        onClick={() => openModal('delete', row.original)}
                    >
                        Delete
                    </DropdownMenuItem>
                </>
            )
        }),
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