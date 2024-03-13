import { useFlashKey } from '@/util/useFlash'
import usePagination from '@/util/usePagination'
import { useDebouncedValue } from '@mantine/hooks'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import deleteLocation from '@/api/admin/locations/deleteLocation'
import { Location, LocationResponse } from '@/api/admin/locations/getLocations'
import useLocationsSWR from '@/api/admin/locations/useLocationsSWR'

import Menu from '@/components/elements/Menu'
import PageContentBlock from '@/components/elements/PageContentBlock'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import Table, {
    Actions,
    ColumnArray,
    RowActionsProps,
} from '@/components/elements/displays/Table'

import SearchBar from '@/components/admin/SearchBar'
import CreateLocationModal from '@/components/admin/locations/CreateLocationModal'
import EditLocationModal from '@/components/admin/locations/EditLocationModal'


const LocationsContainer = () => {
    const { t } = useTranslation('admin.locations')
    const { t: tStrings } = useTranslation('strings')
    const [page, setPage] = usePagination()
    const [isCreating, setIsCreating] = useState(false)
    const { clearFlashes, clearAndAddHttpError } =
        useFlashKey('admin.locations')

    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, mutate } = useLocationsSWR({ page, query: debouncedQuery })

    const columns: ColumnArray<Location> = [
        {
            header: t('short_code'),
            accessor: 'shortCode',
        },
        {
            header: tStrings('description'),
            accessor: 'description',
            overflow: true,
        },
        {
            header: tStrings('node', { count: 2 }),
            accessor: 'nodesCount',
            align: 'center',
        },
        {
            header: tStrings('server', { count: 2 }),
            accessor: 'serversCount',
            align: 'center',
        },
    ]

    const rowActions = ({ row: loc }: RowActionsProps<Location>) => {
        const [isEditing, setIsEditing] = useState(false)
        const handleDelete = () => {
            clearFlashes()

            deleteLocation(loc.id)
                .then(() => {
                    mutate(
                        mutateData =>
                            ({
                                ...mutateData,
                                items: mutateData!.items.filter(
                                    l => l.id !== loc.id
                                ),
                            }) as LocationResponse,
                        false
                    )
                })
                .catch(error => {
                    clearAndAddHttpError(error)
                })
        }

        return (
            <>
                <EditLocationModal
                    mutate={mutate}
                    location={loc}
                    open={isEditing}
                    onClose={() => setIsEditing(false)}
                />
                <Actions>
                    <Menu.Item onClick={() => setIsEditing(true)}>
                        {tStrings('edit')}
                    </Menu.Item>
                    <Menu.Divider />
                    <Menu.Item
                        color='red'
                        disabled={loc.nodesCount > 0}
                        onClick={handleDelete}
                    >
                        {tStrings('delete')}
                    </Menu.Item>
                </Actions>
            </>
        )
    }

    return (
        <div className='bg-background min-h-screen'>
            <PageContentBlock title={tStrings('location', { count: 2 }) ?? ''}>
                <SearchBar
                    value={query}
                    onChange={e => setQuery(e.target.value)}
                    buttonText={t('create_location')}
                    onClick={() => setIsCreating(true)}
                />
                {!data ? (
                    <Spinner />
                ) : (
                    <Pagination data={data} onPageSelect={setPage}>
                        {({ items }) => (
                            <Table
                                columns={columns}
                                data={items}
                                rowActions={rowActions}
                            />
                        )}
                    </Pagination>
                )}
            </PageContentBlock>
            <CreateLocationModal
                open={isCreating}
                onClose={() => setIsCreating(false)}
                mutate={mutate}
            />
        </div>
    )
}

export default LocationsContainer