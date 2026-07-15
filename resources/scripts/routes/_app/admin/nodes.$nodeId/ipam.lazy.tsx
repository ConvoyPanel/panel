import {
    AddressBlockGroupQueryParams,
    useAddressBlockGroups,
} from '@/features/ipam/api.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import { AddressBlockGroup } from '@/types/address-block-group.ts'
import { cn } from '@/utils'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import { Badge } from '@/components/ui/Badge.tsx'
import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import {
    Item,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/nodes/$nodeId/ipam')({
    component: NodeIpam,
})

function pluralize(value: number, singular: string) {
    return `${value} ${singular}${value === 1 ? '' : 's'}`
}

function NodeIpam() {
    const { nodeId } = Route.useParams()
    const numericNodeId = Number(nodeId)
    const { queryParams, tableProps } = useDataTable()
    const scopedQueryParams: AddressBlockGroupQueryParams = {
        ...queryParams,
        filters: {
            ...queryParams.filters,
            node_id: numericNodeId,
        },
    }
    const { data, isPlaceholderData } = useAddressBlockGroups(scopedQueryParams)

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
                    params={{
                        addressBlockGroupId: String(cell.row.original.id),
                    }}
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
            header: 'IP Blocks',
            accessorKey: 'addressBlocksCount',
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
    ]

    return (
        <>
            <Heading>IPAM</Heading>
            <DataTable
                paginated
                searchable
                toolbar
                data={data}
                columns={columns}
                isPlaceholderData={isPlaceholderData}
                mobileRow={row => {
                    const group = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'overflow-x-hidden'}>
                                <ItemTitle>
                                    <Link
                                        className={cn(
                                            buttonVariants({ variant: 'link' }),
                                            'h-auto p-0'
                                        )}
                                        to='/admin/ipam/$addressBlockGroupId'
                                        params={{
                                            addressBlockGroupId: String(
                                                group.id
                                            ),
                                        }}
                                    >
                                        {group.name}
                                    </Link>
                                </ItemTitle>
                                <ItemDescription className={'truncate'}>
                                    {group.description || 'No description'}
                                </ItemDescription>
                                <div className={'flex flex-wrap gap-2'}>
                                    <Badge
                                        variant={'secondary'}
                                        className={'w-fit'}
                                    >
                                        {pluralize(
                                            group.addressBlocksCount,
                                            'IP block'
                                        )}
                                    </Badge>
                                    <Badge
                                        variant={'secondary'}
                                        className={'w-fit'}
                                    >
                                        {pluralize(group.nodesCount, 'node')}
                                    </Badge>
                                </div>
                            </ItemContent>
                        </Item>
                    )
                }}
                {...tableProps}
            />
        </>
    )
}
