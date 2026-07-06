import useClipboard from '@/hooks/use-clipboard.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import { Node } from '@/types/node.ts'
import { cn } from '@/utils'
import { IconPlus } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import byteSize from 'byte-size'

import useNodes from '@/api/admin/nodes/use-nodes.ts'

import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/DropdownMenu'
import { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/nodes')({
    component: NodesIndex,
})

function NodesIndex() {
    const { copy } = useClipboard()
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData } = useNodes(queryParams)

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
            cell: ({ cell }) => {
                const memory = byteSize(cell.getValue<number>(), {
                    units: 'iec',
                })

                return `${memory.value} ${memory.unit}`
            },
        },
        actionsColumn<Node>(_data => (
            <>
                <DropdownMenuItem
                    onClick={() => copy(_data.row.original.id.toString())}
                >
                    Copy ID
                </DropdownMenuItem>{' '}
                <DropdownMenuItem>Edit</DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem>Delete</DropdownMenuItem>
            </>
        )),
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
