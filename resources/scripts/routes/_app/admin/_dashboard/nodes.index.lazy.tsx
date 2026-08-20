import { useNodes } from '@/features/nodes/api.ts'
import useClipboard from '@/hooks/use-clipboard.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import { Node } from '@/types/node.ts'
import { cn } from '@/utils'
import { IconAlertTriangle, IconPlus } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import byteSize from 'byte-size'

import NodeStatusIndicator from '@/features/nodes/components/NodeStatusIndicator.tsx'

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

export const Route = createLazyFileRoute('/_app/admin/_dashboard/nodes/')({
    component: NodesIndex,
})

const formatMemory = (value: number) => {
    const memory = byteSize(value, { units: 'iec' })

    return `${memory.value} ${memory.unit}`
}

function NodesIndex() {
    const { copy } = useClipboard()
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData, isError, refetch } = useNodes(queryParams)

    const renderActions = (node: Node) => (
        <>
            <DropdownMenuItem onClick={() => copy(node.id.toString())}>
                Copy ID
            </DropdownMenuItem>{' '}
            <DropdownMenuItem>Edit</DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem variant={'destructive'}>Delete</DropdownMenuItem>
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
                <span className='flex items-center gap-1.5'>
                    <Link
                        className={cn(
                            buttonVariants({ variant: 'link' }),
                            'px-0'
                        )}
                        to='/admin/nodes/$nodeId'
                        params={{ nodeId: String(cell.row.original.id) }}
                    >
                        {cell.getValue<string>()}
                    </Link>
                    {/* The cluster identity tripwire; the node's own detail
                        page carries the reason and the clear action. */}
                    {cell.row.original.clusterFlaggedAt && (
                        <IconAlertTriangle className='text-destructive size-4 shrink-0' />
                    )}
                </span>
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
            header: 'Status',
            accessorKey: 'status',
            // Written by `nodes:poll`, so this costs no PVE call per row — see
            // docs/node-status-plan.md.
            meta: {
                skeletonWidth: '4rem',
            },
            cell: ({ row }) => <NodeStatusIndicator node={row.original} />,
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
                isError={isError}
                onRetry={refetch}
                mobileRow={row => {
                    const node = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent
                                className={'min-w-0 overflow-x-hidden'}
                            >
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
                                        to='/admin/nodes/$nodeId'
                                        params={{ nodeId: String(node.id) }}
                                    >
                                        <span className={'truncate'}>
                                            {node.displayName}
                                        </span>
                                    </Link>
                                </ItemTitle>
                                <ItemDescription
                                    className={'block truncate text-nowrap'}
                                >
                                    {node.fqdn}
                                </ItemDescription>
                                <ItemDescription className={'flex gap-2'}>
                                    <NodeStatusIndicator node={node} />
                                    <span aria-hidden>·</span>
                                    <span>
                                        {formatMemory(node.memory)} memory
                                    </span>
                                </ItemDescription>
                            </ItemContent>
                            <ItemActions>
                                <Actions>{renderActions(node)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                rightActions={
                    <Link className={buttonVariants()} to='/admin/nodes/create'>
                        <IconPlus className={'size-4'} />
                        Add node
                    </Link>
                }
                {...tableProps}
            />
        </>
    )
}
