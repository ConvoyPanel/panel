import { ServerQueryParams, useServers } from '@/features/servers/admin/api.ts'
import PowerStateBadge from '@/features/servers/components/PowerStateBadge.tsx'
import ServerPowerActions from '@/features/servers/components/admin/ServerPowerActions.tsx'
import useDataTable from '@/hooks/use-data-table.ts'
import { Server } from '@/types/server.ts'
import { cn } from '@/utils'
import { IconServer } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import byteSize from 'byte-size'

import { Badge } from '@/components/ui/Badge.tsx'
import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import DataTableColumnHeader from '@/components/ui/DataTable/DataTableColumnHeader.tsx'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'
import Actions, { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/users/$userId/servers')({
    component: UserServers,
})

const format = (bytes: number) => {
    const size = byteSize(bytes, { units: 'iec', precision: 0 })

    return `${size.value} ${size.unit}`
}

function UserServers() {
    const { userId } = Route.useParams()
    const numericUserId = Number(userId)
    const { queryParams, tableProps } = useDataTable()

    // The owner filter is fixed by the route and merged over whatever the toolbar contributes —
    // the same shape the node's own servers tab uses for `node_id`.
    const scopedQueryParams: ServerQueryParams = {
        ...queryParams,
        filters: {
            ...queryParams.filters,
            user_id: numericUserId,
        },
    }

    const { data, isPlaceholderData, isError, refetch } =
        useServers(scopedQueryParams)

    const columns: ColumnDef<Server>[] = [
        {
            accessorKey: 'name',
            enableHiding: false,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={'Name'} />
            ),
            meta: { skeletonWidth: '6rem' },
            cell: ({ cell }) => (
                <span className={'flex items-center gap-1.5'}>
                    <Link
                        className={cn(
                            buttonVariants({ variant: 'link' }),
                            'px-0'
                        )}
                        to={`/admin/servers/${cell.row.original.id}` as string}
                    >
                        {cell.getValue<string>()}
                    </Link>
                    {/* Separate from power state, not a value of it -- a suspended server can be
                        running, and the tile above counts these. */}
                    {cell.row.original.suspendedAt && (
                        <Badge variant={'destructive'}>Suspended</Badge>
                    )}
                </span>
            ),
        },
        {
            header: 'Hostname',
            accessorKey: 'hostname',
            meta: { skeletonWidth: '6rem' },
        },
        {
            header: 'Power',
            accessorKey: 'powerState',
            meta: { skeletonWidth: '4rem' },
            cell: ({ row }) => (
                <PowerStateBadge state={row.original.powerState} />
            ),
        },
        {
            header: 'vCPU',
            accessorKey: 'cpu',
            meta: { skeletonWidth: '2rem' },
            cell: ({ cell }) => (
                <span className={'tabular-nums'}>{cell.getValue<number>()}</span>
            ),
        },
        {
            header: 'Memory',
            accessorKey: 'memory',
            meta: { skeletonWidth: '4rem' },
            cell: ({ cell }) => (
                <span className={'whitespace-nowrap tabular-nums'}>
                    {format(cell.getValue<number>())}
                </span>
            ),
        },
        {
            header: 'Disk',
            accessorKey: 'disk',
            meta: { skeletonWidth: '4rem' },
            cell: ({ cell }) => (
                <span className={'whitespace-nowrap tabular-nums'}>
                    {format(cell.getValue<number>())}
                </span>
            ),
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
                isPlaceholderData={isPlaceholderData}
                isError={isError}
                onRetry={refetch}
                emptyState={
                    <SimpleEmptyState
                        icon={IconServer}
                        title={'No servers'}
                        description={
                            'Servers created for this account will be listed here.'
                        }
                    />
                }
                mobileRow={row => {
                    const server = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'min-w-0'}>
                                <ItemTitle className={'w-full min-w-0 gap-2'}>
                                    <Link
                                        className={'truncate'}
                                        to={
                                            `/admin/servers/${server.id}` as string
                                        }
                                    >
                                        {server.name}
                                    </Link>
                                    <PowerStateBadge state={server.powerState} />
                                    {server.suspendedAt && (
                                        <Badge variant={'destructive'}>
                                            Suspended
                                        </Badge>
                                    )}
                                </ItemTitle>
                                <ItemDescription
                                    className={'block truncate text-nowrap'}
                                >
                                    {server.hostname}
                                </ItemDescription>
                                <ItemDescription>
                                    {server.cpu} vCPU &middot;{' '}
                                    {format(server.memory)} &middot;{' '}
                                    {format(server.disk)}
                                </ItemDescription>
                            </ItemContent>
                            <ItemActions>
                                <Actions>
                                    <ServerPowerActions server={server} />
                                </Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                {...tableProps}
            />
        </>
    )
}
