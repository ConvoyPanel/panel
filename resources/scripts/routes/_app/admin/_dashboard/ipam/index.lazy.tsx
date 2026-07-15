import useDataTable from '@/hooks/use-data-table.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import {
    AddressBlockGroup,
    PaginatedAddressBlockGroups,
} from '@/types/address-block-group.ts'
import { cn } from '@/utils'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import { useShallow } from 'zustand/react/shallow'

import {
    useAddressBlockGroups,
    addressBlockGroupQueries,
} from '@/features/ipam/api.ts'

import CreateBlockGroupModal from '@/features/ipam/components/CreateBlockGroupModal.tsx'
import DeleteBlockGroupModal from '@/features/ipam/components/DeleteBlockGroupModal.tsx'
import EditBlockGroupModal from '@/features/ipam/components/EditBlockGroupModal.tsx'
import useBlockGroupModalStore from '@/features/ipam/hooks/use-block-group-modal-store.ts'

import { Badge } from '@/components/ui/Badge.tsx'
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
    const { data, isPlaceholderData } = useAddressBlockGroups(queryParams)
    const mutate = useQueryMutator<PaginatedAddressBlockGroups>(
        addressBlockGroupQueries.list(queryParams).queryKey
    )
    const openModal = useBlockGroupModalStore(
        useShallow(state => state.openModal)
    )

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
                skeletonWidth: '5rem',
            },
            cell: ({ cell }) => (
                <Link
                    className={cn(buttonVariants({ variant: 'link' }), 'px-0')}
                    to='/admin/ipam/$addressBlockGroupId'
                    params={{ addressBlockGroupId: String(cell.row.original.id) }}
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
        actionsColumn<AddressBlockGroup>(({ row }) =>
            renderActions(row.original)
        ),
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
                isPlaceholderData={isPlaceholderData}
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
                                            'h-auto min-w-0 max-w-full shrink p-0'
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
                                <Badge
                                    variant={'secondary'}
                                    className={'w-fit font-mono'}
                                >
                                    {group.nodesCount}{' '}
                                    {group.nodesCount === 1 ? 'node' : 'nodes'}
                                </Badge>
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
