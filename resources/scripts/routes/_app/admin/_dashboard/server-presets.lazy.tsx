import {
    serverPresetQueries,
    useServerPresets,
} from '@/features/servers/presets/api.ts'
import DeletePresetModal from '@/features/servers/presets/components/DeletePresetModal.tsx'
import EditPresetModal from '@/features/servers/presets/components/EditPresetModal.tsx'
import ShowPresetModal from '@/features/servers/presets/components/ShowPresetModal.tsx'
import { describePresetSettings } from '@/features/servers/presets/form-mapping.ts'
import createModalStore, { useOpenModal } from '@/hooks/create-modal-store.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import type { ServerPreset } from '@/types/server-preset'
import { cn } from '@/utils'
import { IconPlus, IconStack2 } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'

import { Badge } from '@/components/ui/Badge.tsx'
import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/DropdownMenu'
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

export const useServerPresetsModalStore = createModalStore<
    ServerPreset,
    'show' | 'edit' | 'delete'
>()

/**
 * Presets are *written* from the server create form, which is the only screen
 * that knows what a workable build looks like — so this page manages them
 * (rename, inspect, delete) rather than offering a second way to author one.
 */
const ServerPresetsIndex = () => {
    const openModal = useOpenModal(useServerPresetsModalStore)
    const { data, isLoading, isError, refetch } = useServerPresets()
    const mutate = useQueryMutator<ServerPreset[]>(
        serverPresetQueries.list().queryKey
    )

    const renderActions = (preset: ServerPreset) => (
        <>
            <DropdownMenuItem onClick={() => openModal('show', preset)}>
                View settings
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => openModal('edit', preset)}>
                Edit
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                variant={'destructive'}
                onClick={() => openModal('delete', preset)}
            >
                Delete
            </DropdownMenuItem>
        </>
    )

    const columns: ColumnDef<ServerPreset>[] = [
        {
            header: 'Name',
            accessorKey: 'name',
            enableHiding: false,
            meta: { skeletonWidth: '6rem' },
            cell: ({ cell }) => (
                <button
                    className={cn(buttonVariants({ variant: 'link' }), 'px-0')}
                    onClick={() => openModal('show', cell.row.original)}
                >
                    {cell.getValue<string>()}
                </button>
            ),
        },
        {
            header: 'Description',
            accessorKey: 'description',
            meta: { skeletonWidth: '10rem' },
        },
        {
            header: 'Build',
            id: 'build',
            meta: { skeletonWidth: '10rem' },
            cell: ({ row }) => (
                <span className={'text-muted-foreground'}>
                    {describePresetSettings(row.original.settings)}
                </span>
            ),
        },
        {
            header: 'Scope',
            id: 'scope',
            meta: { skeletonWidth: '4rem', align: 'center' },
            cell: ({ row }) =>
                // A preset that names a node also carries that node's storage
                // and bridge, so it only applies cleanly there — worth saying
                // in the list rather than only inside the detail modal.
                row.original.settings.nodeId != null ? (
                    <Badge variant={'secondary'}>Node-specific</Badge>
                ) : (
                    <Badge variant={'outline'}>Any node</Badge>
                ),
            maxSize: 60,
        },
        actionsColumn<ServerPreset>(({ row }) => renderActions(row.original)),
    ]

    return (
        <>
            <Heading>Server Presets</Heading>
            <DataTable
                data={data}
                columns={columns}
                isPlaceholderData={isLoading}
                isError={isError}
                onRetry={refetch}
                emptyState={
                    <SimpleEmptyState
                        icon={IconStack2}
                        title={'No presets'}
                        description={
                            'Save a configuration from the server create form and it appears here.'
                        }
                        action={
                            <Link
                                className={buttonVariants()}
                                to={'/admin/servers/create'}
                            >
                                <IconPlus className={'size-4'} />
                                Add server
                            </Link>
                        }
                    />
                }
                mobileRow={row => {
                    const preset = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'overflow-x-hidden'}>
                                <ItemTitle>
                                    <button
                                        className={cn(
                                            buttonVariants({ variant: 'link' }),
                                            'h-auto p-0'
                                        )}
                                        onClick={() =>
                                            openModal('show', preset)
                                        }
                                    >
                                        {preset.name}
                                    </button>
                                </ItemTitle>
                                <ItemDescription className={'truncate'}>
                                    {preset.description ??
                                        describePresetSettings(preset.settings)}
                                </ItemDescription>
                            </ItemContent>
                            <ItemActions>
                                <Actions>{renderActions(preset)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
            />
            <ShowPresetModal />
            <EditPresetModal mutate={mutate} />
            <DeletePresetModal mutate={mutate} />
        </>
    )
}

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/server-presets'
)({
    component: ServerPresetsIndex,
})
