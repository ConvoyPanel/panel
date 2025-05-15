import usePagination from '@/hooks/use-pagination.ts'
import { AddressBlock } from '@/types/address-block.ts'
import { AddressVersion } from '@/types/address.ts'
import { createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import useAddressBlocksSWR from '@/api/admin/addressBlockGroups/addressBlocks/use-address-blocks-swr.ts'
import useAddressBlockGroupSWR from '@/api/admin/addressBlockGroups/use-address-block-group-swr.ts'

import { Badge } from '@/components/ui/Badge.tsx'
import { DataTable } from '@/components/ui/DataTable'
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/DropdownMenu'
import { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/ipam/$addressBlockGroupId/'
)({
    component: GroupBlocks,
})

function GroupBlocks() {
    const { data: group } = useAddressBlockGroupSWR()
    const pagination = usePagination()
    const { data } = useAddressBlocksSWR({
        page: pagination.page,
        filters: {
            '*': pagination.debouncedQuery,
        },
    })

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
        actionsColumn(({ row: _ }) => (
            <>
                <DropdownMenuItem>Edit</DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem>Delete</DropdownMenuItem>
            </>
        )),
    ]

    return (
        <>
            <Heading>{group?.name}</Heading>
            <DataTable
                data={data}
                columns={columns}
                paginated
                searchable
                toolbar
                rightActions={<></>}
                {...pagination}
            />
        </>
    )
}
