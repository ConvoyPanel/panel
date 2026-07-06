import createModalStore from '@/hooks/create-modal-store.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import { Location } from '@/features/locations/types.ts'
import { cn } from '@/utils'
import { createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import { useShallow } from 'zustand/react/shallow'

import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { PaginatedLocations } from '@/features/locations/types.ts'

import { locationQueries, useLocations } from '@/features/locations/api.ts'

import CreateLocationModal from '@/features/locations/components/CreateLocationModal.tsx'
import DeleteLocationModal from '@/features/locations/components/DeleteLocationModal.tsx'
import EditLocationModal from '@/features/locations/components/EditLocationModal.tsx'
import ShowLocationModal from '@/features/locations/components/ShowLocationModal.tsx'

import { Badge } from '@/components/ui/Badge.tsx'
import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/DropdownMenu'
import { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/locations')({
    component: LocationsIndex,
})

export const useLocationsModalStore = createModalStore<
    Location,
    'show' | 'edit' | 'delete'
>()

function LocationsIndex() {
    const openModal = useLocationsModalStore(
        useShallow(state => state.openModal)
    )
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData } = useLocations(queryParams)
    const mutate = useQueryMutator<PaginatedLocations>(
        locationQueries.list(queryParams).queryKey
    )

    const columns: ColumnDef<Location>[] = [
        {
            header: 'Short Code',
            accessorKey: 'shortCode',
            enableHiding: false,
            meta: {
                skeletonWidth: '5rem',
            },
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
            meta: {
                skeletonWidth: '10rem',
            },
        },
        {
            header: 'Nodes',
            accessorKey: 'nodesCount',
            meta: {
                skeletonWidth: '1rem',
                align: 'center',
            },
            cell: ({ cell }) => (
                <Badge variant={'secondary'} className={'font-mono'}>
                    {cell.getValue<number>()}
                </Badge>
            ),
            maxSize: 40,
        },
        {
            header: 'Servers',
            accessorKey: 'serversCount',
            meta: {
                skeletonWidth: '1rem',
                align: 'center',
            },
            cell: ({ cell }) => (
                <Badge variant={'secondary'} className={'font-mono'}>
                    {cell.getValue<number>()}
                </Badge>
            ),
            maxSize: 40,
        },
        actionsColumn<Location>(({ row }) => (
            <>
                <DropdownMenuItem
                    onClick={() => openModal('edit', row.original)}
                >
                    Edit
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    onClick={() => openModal('delete', row.original)}
                >
                    Delete
                </DropdownMenuItem>
            </>
        )),
    ]

    return (
        <>
            <Heading>Locations</Heading>
            <DataTable
                data={data}
                columns={columns}
                paginated
                searchable
                toolbar
                isPlaceholderData={isPlaceholderData}
                rightActions={<CreateLocationModal mutate={mutate} />}
                {...tableProps}
            />
            <ShowLocationModal />
            <EditLocationModal mutate={mutate} />
            <DeleteLocationModal mutate={mutate} />
        </>
    )
}
