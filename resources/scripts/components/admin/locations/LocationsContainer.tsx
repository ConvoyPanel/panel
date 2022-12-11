import Table from '@/components/elements/displays/Table'
import PageContentBlock from '@/components/elements/PageContentBlock'
import useSWR from 'swr'
import usePagination from '@/util/usePagination'
import getLocations, { Location } from '@/api/admin/locations/getLocations'
import Spinner from '@/components/elements/Spinner'
import { Column } from '@/components/elements/displays/Table'
import Pagination from '@/components/elements/Pagination'
import Button from '@/components/elements/Button'
import EditLocationModal from '@/components/admin/locations/EditLocationModal'
import { useState } from 'react'

const columns: Column<Location>[] = [
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

    const { data, mutate } = useSWR(['admin:locations', page], () => getLocations({ page }))

    const rowActions = (locationIndex: number) => {
        return
    }

    return (
        <div className='bg-background min-h-screen'>
            <EditLocationModal mutate={mutate} open={open} onClose={() => setOpen(false)} />
            <PageContentBlock title='Locations' showFlashKey='admin:locations'>
                <div className='flex justify-end items-center mb-3'>
                    <Button onClick={() => setOpen(true)} variant='filled'>
                        New Location
                    </Button>
                </div>
                {!data ? (
                    <Spinner />
                ) : (
                    <Pagination data={data} onPageSelect={setPage}>
                        {({ items }) => <Table columns={columns} data={items} />}
                    </Pagination>
                )}
            </PageContentBlock>
        </div>
    )
}

export default LocationsContainer
