import usePagination from '@/hooks/use-pagination.ts'
import { AddressBlockGroup } from '@/types/address-block-group.ts'
import { cn } from '@/utils'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import { useShallow } from 'zustand/react/shallow'

import useAddressBlockGroupsSWR from '@/api/admin/addressBlockGroups/use-address-block-groups-swr.ts'

import CreateBlockGroupModal from '@/components/interfaces/Admin/Ipam/CreateBlockGroupModal.tsx'
import DeleteBlockGroupModal from '@/components/interfaces/Admin/Ipam/DeleteBlockGroupModal.tsx'
import EditBlockGroupModal from '@/components/interfaces/Admin/Ipam/EditBlockGroupModal.tsx'
import useBlockGroupModalStore from '@/components/interfaces/Admin/Ipam/use-block-group-modal-store.ts'

import { Badge } from '@/components/ui/Badge.tsx'
import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/DropdownMenu'
import { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/ipam/')({
    component: IpamIndex,
})

function IpamIndex() {
    const pagination = usePagination()
    const { data, mutate } = useAddressBlockGroupsSWR({
        page: pagination.page,
        filters: {
            '*': pagination.debouncedQuery,
        },
    })
    const openModal = useBlockGroupModalStore(
        useShallow(state => state.openModal)
    )

    const columns: ColumnDef<AddressBlockGroup>[] = [
        {
            header: 'IP Block Group',
            accessorKey: 'name',
            enableHiding: false,
            meta: {
                skeletonWidth: '5rem',
            },
            cell: ({ cell }) => (
                <Link
                    className={cn(buttonVariants({ variant: 'link' }), 'px-0')}
                    to={`/admin/ipam/${cell.row.original.id}`}
                >
                    {cell.getValue<string>()}
                </Link>
            ),
        },
        {
            header: 'Description',
            accessorKey: 'description',
            meta: {
                skeletonWidth: '10rem',
            },
        },
        {
            header: 'Nodes',
            accessorKey: 'nodesCount',
            meta: {
                skeletonWidth: '1rem',
                align: 'center',
            },
            cell: ({ cell }) => (
                <Badge variant={'secondary'} className={'font-mono'}>
                    {cell.getValue<number>()}
                </Badge>
            ),
        },
        actionsColumn(({ row }) => {
            return (
                <>
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
        <>
            <Heading>IPAM</Heading>
            <DataTable
                data={data}
                columns={columns}
                paginated
                searchable
                toolbar
                rightActions={<CreateBlockGroupModal mutate={mutate} />}
                {...pagination}
            />
            <EditBlockGroupModal mutate={mutate} />
            <DeleteBlockGroupModal mutate={mutate} />
        </>
    )
}
