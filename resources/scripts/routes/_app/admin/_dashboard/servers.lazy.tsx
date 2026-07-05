import usePagination from '@/hooks/use-pagination.ts'
import { Server } from '@/types/server.ts'
import { cn } from '@/utils'
import { IconPlus } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import useServersSWR from '@/api/admin/servers/use-servers-swr.ts'

import ServerPowerActions from '@/components/interfaces/Admin/Server/ServerPowerActions.tsx'
import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/servers')({
    component: ServersIndex,
})

function ServersIndex() {
    const pagination = usePagination()
    const { data } = useServersSWR({
        page: pagination.page,
        filters: {
            '*': pagination.debouncedQuery,
        },
    })

    const columns: ColumnDef<Server>[] = [
        {
            header: 'Name',
            accessorKey: 'name',
            enableHiding: false,
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
                data={data}
                columns={columns}
                rightActions={
                    <Link
                        className={cn(buttonVariants({ size: 'sm' }), 'flex')}
                        to='/admin/servers/create'
                    >
                        <IconPlus className={'mr-2 size-4'} />
                        Add server
                    </Link>
                }
                {...pagination}
            />
        </>
    )
}
