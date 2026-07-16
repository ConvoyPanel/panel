import {
    addressBlockQueries,
    useAddressBlocks,
} from '@/features/ipam/blocks/api.ts'
import CreateAddressBlockModal from '@/features/ipam/components/AddressBlock/CreateAddressBlockModal.tsx'
import DeleteAddressBlockModal from '@/features/ipam/components/AddressBlock/DeleteAddressBlockModal.tsx'
import EditAddressBlockModal from '@/features/ipam/components/AddressBlock/EditAddressBlockModal.tsx'
import { useAddressBlockModal } from '@/features/ipam/hooks/use-address-block-modal.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId/index.lazy.tsx'
import { AddressBlock, PaginatedAddressBlocks } from '@/types/address-block.ts'
import { AddressVersion } from '@/types/address.ts'
import { cn } from '@/utils'
import { Link } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

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
import { TabsContent } from '@/components/ui/Tabs'

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
                                            'h-auto max-w-full min-w-0 shrink p-0'
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
                                            {block.name}
                                        </span>
                                    </Link>
                                </ItemTitle>
                                <ItemDescription
                                    className={'block truncate text-nowrap'}
                                >
                                    {block.description || 'No description'}
                                </ItemDescription>
                                <div className={'flex flex-wrap gap-2'}>
                                    <Badge
                                        variant={'secondary'}
                                        className={'font-mono'}
                                    >
                                        {block.baseIp}/{block.prefixLengthFrom}
                                    </Badge>
                                    <Badge variant={'secondary'}>
                                        {block.version === AddressVersion.IPv4
                                            ? 'IPv4'
                                            : 'IPv6'}
                                    </Badge>
                                </div>
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
