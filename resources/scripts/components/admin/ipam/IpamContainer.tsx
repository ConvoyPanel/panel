import { useTranslation } from 'react-i18next'
import PageContentBlock from '@/components/elements/PageContentBlock'
import Table, { ColumnArray } from '@/components/elements/displays/Table'
import { Location } from '@/api/admin/locations/getLocations'
import { AddressPool } from '@/api/admin/nodes/addressPools/getAddressPools'
import usePagination from '@/util/usePagination'
import { useState } from 'react'
import { useDebouncedValue } from '@mantine/hooks'
import useAddressPoolsSWR from '@/api/admin/nodes/addressPools/useAddressPoolsSWR'
import Spinner from '@/components/elements/Spinner'
import Pagination from '@/components/elements/Pagination'
import SearchBar from '@/components/admin/SearchBar'

const IpamContainer = () => {
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.nodes.addressPools')
    const [page, setPage] = usePagination()
    const [isCreating, setIsCreating] = useState(false)

    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data } = useAddressPoolsSWR({ page, query: debouncedQuery })

    const columns: ColumnArray<AddressPool> = [
        {
            header: tStrings('name'),
            accessor: 'name',
        },
        {
            header: tStrings('node', { count: 2 }),
            accessor: 'nodesCount',
            align: 'center',
        },
        {
            header: tStrings('address_other'),
            accessor: 'addressesCount',
            align: 'center',
        },
    ]

    return (
        <div className='bg-background min-h-screen'>
            <PageContentBlock title={tStrings('ipam') ?? ''}>
                <SearchBar
                    value={query}
                    onChange={e => setQuery(e.target.value)}
                    buttonText={t('create_pool')}
                    onClick={() => setIsCreating(true)}
                />
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

export default IpamContainer
