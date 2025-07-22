import usePagination from '@/hooks/use-pagination.ts'
import { Server } from '@/types/server.ts'
import { cn } from '@/utils'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import { buttonVariants } from '@/components/ui/Button'
import { Heading } from '@/components/ui/Typography'
import { DataTable } from '@/components/ui/DataTable'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/servers')({
    component: ServersIndex,
})

function ServersIndex() {
    const pagination = usePagination()

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
                    to={`/admin/servers/${cell.row.original.id}`}
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
            }
        },
    ]

    return <>
        <Heading>Servers</Heading>
    </>
}
