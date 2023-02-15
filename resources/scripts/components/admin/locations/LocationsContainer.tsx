import Table, { Actions, ColumnArray, RowActionsProps } from '@/components/elements/displays/Table'
import PageContentBlock from '@/components/elements/PageContentBlock'
import useSWR from 'swr'
import usePagination from '@/util/usePagination'
import getLocations, { Location, LocationResponse } from '@/api/admin/locations/getLocations'
import Spinner from '@/components/elements/Spinner'
import { Column } from '@/components/elements/displays/Table'
import Pagination from '@/components/elements/Pagination'
import Button from '@/components/elements/Button'
import EditLocationModal from '@/components/admin/locations/EditLocationModal'
import { useState } from 'react'
import Menu from '@/components/elements/Menu'
import deleteLocation from '@/api/admin/locations/deleteLocation'
import useFlash from '@/util/useFlash'
import { useDebouncedValue } from '@mantine/hooks'
import useLocationsSWR from '@/api/admin/locations/useLocationsSWR'
import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/react/20/solid'
import TextInput from '@/components/elements/inputs/TextInput'
import SearchBar from '@/components/admin/SearchBar'

const columns: ColumnArray<Location> = [
    {
        header: 'Short Code',
        accessor: 'shortCode',
    },
    {
        header: 'Description',
        accessor: 'description',
        overflow: true,
    },
    {
        header: 'Nodes',
        accessor: 'nodesCount',
        align: 'center',
    },
    {
        header: 'Servers',
        accessor: 'serversCount',
        align: 'center',
    },
]

const LocationsContainer = () => {
    const [page, setPage] = usePagination()
    const [open, setOpen] = useState(false)
    const { clearFlashes, clearAndAddHttpError } = useFlash()

    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, mutate } = useLocationsSWR({ page, query: debouncedQuery })

    const [location, setLocation] = useState<Location | undefined>(undefined)

    const rowActions = ({ row: loc }: RowActionsProps<Location>) => {
        const handleEdit = () => {
            setLocation(loc)
            setOpen(true)
        }

        const handleDelete = () => {
            clearFlashes('admin:locations')

            deleteLocation(loc.id)
                .then(() => {
                    mutate(
                        mutateData =>
                            ({
                                ...mutateData,
                                items: mutateData!.items.filter(l => l.id !== loc.id),
                            } as LocationResponse),
                        false
                    )
                })
                .catch(error => {
                    clearAndAddHttpError({ key: 'admin:locations', error })
                })
        }

        return (
            <Actions>
                <Menu.Item onClick={handleEdit}>Edit</Menu.Item>
                <Menu.Divider />
                <Menu.Item color='danger' disabled={loc.nodesCount > 0} onClick={handleDelete}>
                    Delete
                </Menu.Item>
            </Actions>
        )
    }

    const handleClose = () => {
        setOpen(false)
        setLocation(undefined)
    }

    return (
        <div className='bg-background min-h-screen'>
            <EditLocationModal location={location} mutate={mutate} open={open} onClose={handleClose} />
            <PageContentBlock title='Locations' showFlashKey='admin:locations'>
                <SearchBar value={query} onChange={e => setQuery(e.target.value)} buttonText='New Location' onClick={() => setOpen(true)} />
                {!data ? (
                    <Spinner />
                ) : (
                    <Pagination data={data} onPageSelect={setPage}>
                        {({ items }) => <Table columns={columns} data={items} rowActions={rowActions} />}
                    </Pagination>
                )}
            </PageContentBlock>
        </div>
    )
}

export default LocationsContainer
