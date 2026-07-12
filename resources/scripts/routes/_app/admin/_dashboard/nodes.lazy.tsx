import useClipboard from '@/hooks/use-clipboard.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import { Node } from '@/types/node.ts'
import { cn } from '@/utils'
import { IconPlus } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import byteSize from 'byte-size'

import { useNodes } from '@/features/nodes/api.ts'

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

export const Route = createLazyFileRoute('/_app/admin/_dashboard/nodes')({
    component: NodesIndex,
})

const formatMemory = (value: number) => {
    const memory = byteSize(value, { units: 'iec' })

    return `${memory.value} ${memory.unit}`
}

function NodesIndex() {
    const { copy } = useClipboard()
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData } = useNodes(queryParams)

    const renderActions = (node: Node) => (
        <>
            <DropdownMenuItem onClick={() => copy(node.id.toString())}>
                Copy ID
            </DropdownMenuItem>{' '}
            <DropdownMenuItem>Edit</DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem>Delete</DropdownMenuItem>
        </>
    )

    const columns: ColumnDef<Node>[] = [
        {
            header: 'Name',
            accessorKey: 'displayName',
            enableHiding: false,
            meta: {
                skeletonWidth: '5rem',
            },
            cell: ({ cell }) => (
                <Link
                    className={cn(buttonVariants({ variant: 'link' }), 'px-0')}
                    to='/admin/nodes/$nodeId'
                    params={{ nodeId: String(cell.row.original.id) }}
                >
                    {cell.getValue<string>()}
                </Link>
            ),
        },
        {
            header: 'FQDN',
            accessorKey: 'fqdn',
            meta: {
                skeletonWidth: '7rem',
            },
        },
        {
            header: 'Memory',
            accessorKey: 'memory',
            meta: {
                skeletonWidth: '1rem',
            },
            cell: ({ cell }) => formatMemory(cell.getValue<number>()),
        },
        actionsColumn<Node>(({ row }) => renderActions(row.original)),
    ]

    return (
        <>
            <Heading>Nodes</Heading>
            <DataTable
                paginated
                searchable
                toolbar
                data={data}
                columns={columns}
                isPlaceholderData={isPlaceholderData}
                mobileRow={row => {
                    const node = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'overflow-x-hidden'}>
                                <ItemTitle>
                                    <Link
                                        className={cn(
                                            buttonVariants({ variant: 'link' }),
                                            'h-auto p-0'
                                        )}
                                        to='/admin/nodes/$nodeId'
                                        params={{ nodeId: String(node.id) }}
                                    >
                                        {node.displayName}
                                    </Link>
                                </ItemTitle>
                                <ItemDescription className={'truncate'}>
                                    {node.fqdn}
                                </ItemDescription>
                                <ItemDescription>
                                    {formatMemory(node.memory)} memory
                                </ItemDescription>
                            </ItemContent>
                            <ItemActions>
                                <Actions>{renderActions(node)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                rightActions={
                    <Link
                        className={cn(buttonVariants({ size: 'sm' }), 'flex')}
                        to='/admin/nodes/create'
                    >
                        <IconPlus className={'mr-2 size-4'} />
                        Add node
                    </Link>
                }
                {...tableProps}
            />
        </>
    )
}
