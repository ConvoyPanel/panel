import { useServers } from '@/features/servers/admin/api.ts'
import ServerBulkPowerActions from '@/features/servers/components/admin/ServerBulkPowerActions.tsx'
import ServerPowerActions from '@/features/servers/components/admin/ServerPowerActions.tsx'
import PowerStateBadge from '@/features/servers/components/PowerStateBadge.tsx'
import useDataTable from '@/hooks/use-data-table.ts'
import { Server } from '@/types/server.ts'
import { cn } from '@/utils'
import { IconPlus, IconServer } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import { buttonVariants } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import { DataTable } from '@/components/ui/DataTable'
import DataTableColumnHeader from '@/components/ui/DataTable/DataTableColumnHeader.tsx'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'
import Actions, { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/servers')({
    component: ServersIndex,
})

function ServersIndex() {
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData, isError, refetch } =
        useServers(queryParams)

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
                enableRowSelection
                data={data}
                columns={columns}
                isPlaceholderData={isPlaceholderData}
                isError={isError}
                onRetry={refetch}
                bulkActions={servers => (
                    <ServerBulkPowerActions servers={servers} />
                )}
                rightActions={
                    <Link
                        className={buttonVariants()}
                        to='/admin/servers/create'
                    >
                        <IconPlus className={'size-4'} />
                        Add server
                    </Link>
                }
                emptyState={
                    <SimpleEmptyState
                        icon={IconServer}
                        title={'No servers'}
                        description={
                            'Servers you provision across your nodes appear here.'
                        }
                        action={
                            <Link
                                className={buttonVariants()}
                                to='/admin/servers/create'
                            >
                                <IconPlus className={'size-4'} />
                                Add server
                            </Link>
                        }
                    />
                }
                mobileRow={row => {
                    const server = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            {/* The desktop table selects via its own checkbox
                                column, which mobile does not render — carry one
                                here so bulk power actions stay reachable. */}
                            <ItemMedia>
                                <Checkbox
                                    checked={row.getIsSelected()}
                                    onCheckedChange={value =>
                                        row.toggleSelected(!!value)
                                    }
                                    aria-label={`Select ${server.name}`}
                                />
                            </ItemMedia>
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
                                <ItemDescription
                                    className={'block truncate text-nowrap'}
                                >
                                    {server.hostname}
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
