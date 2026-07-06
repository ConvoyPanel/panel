import useDataTable from '@/hooks/use-data-table.ts'
import { Server } from '@/types/server.ts'
import { cn } from '@/utils'
import { IconPlus } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import { useServers } from '@/features/servers/admin/api.ts'

import ServerBulkPowerActions from '@/components/interfaces/Admin/Server/ServerBulkPowerActions.tsx'
import ServerPowerActions from '@/components/interfaces/Admin/Server/ServerPowerActions.tsx'
import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import DataTableColumnHeader from '@/components/ui/DataTable/DataTableColumnHeader.tsx'
import { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/servers')({
    component: ServersIndex,
})

function ServersIndex() {
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData } = useServers(queryParams)

    const columns: ColumnDef<Server>[] = [
        {
            accessorKey: 'name',
            enableHiding: false,
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title='Name' />
            ),
            meta: {
                skeletonWidth: '5rem',
            },
            cell: ({ cell }) => (
                <Link
                    className={cn(buttonVariants({ variant: 'link' }), 'px-0')}
                    to={`/admin/servers/${cell.row.original.id}` as string}
                >
                    {cell.getValue<string>()}
                </Link>
            ),
        },
        {
            header: 'Hostname',
            accessorKey: 'hostname',
            meta: {
                skeletonWidth: '4rem',
            },
        },
        actionsColumn<Server>(({ row }) => (
            <ServerPowerActions server={row.original} />
        )),
    ]

    return (
        <>
            <Heading>Servers</Heading>
            <DataTable
                paginated
                searchable
                toolbar
                enableRowSelection
                data={data}
                columns={columns}
                isPlaceholderData={isPlaceholderData}
                bulkActions={servers => (
                    <ServerBulkPowerActions servers={servers} />
                )}
                rightActions={
                    <Link
                        className={cn(buttonVariants({ size: 'sm' }), 'flex')}
                        to='/admin/servers/create'
                    >
                        <IconPlus className={'mr-2 size-4'} />
                        Add server
                    </Link>
                }
                {...tableProps}
            />
        </>
    )
}
