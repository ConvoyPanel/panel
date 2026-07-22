import { ServerQueryParams, useServers } from '@/features/servers/admin/api.ts'
import ServerPowerActions from '@/features/servers/components/admin/ServerPowerActions.tsx'
import PowerStateBadge from '@/features/servers/components/PowerStateBadge.tsx'
import useDataTable from '@/hooks/use-data-table.ts'
import { Server } from '@/types/server.ts'
import { cn } from '@/utils'
import { IconPlus } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import { Badge } from '@/components/ui/Badge.tsx'
import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import DataTableColumnHeader from '@/components/ui/DataTable/DataTableColumnHeader.tsx'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'
import Actions, { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/nodes/$nodeId/servers')({
    component: NodeServers,
})

const statusLabel = (status: Server['status']) => status.replace(/_/g, ' ')

function NodeServers() {
    const { nodeId } = Route.useParams()
    const numericNodeId = Number(nodeId)
    const { queryParams, tableProps } = useDataTable()
    const scopedQueryParams: ServerQueryParams = {
        ...queryParams,
        filters: {
            ...queryParams.filters,
            node_id: numericNodeId,
        },
    }
    const { data, isPlaceholderData, isError, refetch } =
        useServers(scopedQueryParams)

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
        {
            header: 'Power',
            accessorKey: 'powerState',
            cell: ({ row }) => (
                <PowerStateBadge state={row.original.powerState} />
            ),
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
                isPlaceholderData={isPlaceholderData}
                isError={isError}
                onRetry={refetch}
                mobileRow={row => {
                    const server = row.original

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
                                        to={
                                            `/admin/servers/${server.id}` as string
                                        }
                                    >
                                        <span className={'truncate'}>
                                            {server.name}
                                        </span>
                                    </Link>
                                    <PowerStateBadge
                                        state={server.powerState}
                                    />
                                </ItemTitle>
                                {/* `block`/`text-nowrap` beat ItemDescription's
                                    default line-clamp-2 + text-balance, which
                                    otherwise silently defeat `truncate`. */}
                                <ItemDescription
                                    className={'block truncate text-nowrap'}
                                >
                                    {server.hostname}
                                </ItemDescription>
                                <Badge
                                    variant={'secondary'}
                                    className={'w-fit capitalize'}
                                >
                                    {statusLabel(server.status)}
                                </Badge>
                            </ItemContent>
                            <ItemActions>
                                <Actions>
                                    <ServerPowerActions server={server} />
                                </Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                rightActions={
                    <Link
                        className={buttonVariants()}
                        to='/admin/servers/create'
                    >
                        <IconPlus className={'size-4'} />
                        Add server
                    </Link>
                }
                {...tableProps}
            />
        </>
    )
}
