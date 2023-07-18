import { useTranslation } from 'react-i18next'
import PageContentBlock from '@/components/elements/PageContentBlock'
import Table, { ColumnArray } from '@/components/elements/displays/Table'
import { AddressPool } from '@/api/admin/addressPools/getAddressPools'
import usePagination from '@/util/usePagination'
import { useState } from 'react'
import { useDebouncedValue } from '@mantine/hooks'
import useAddressPoolsSWR from '@/api/admin/addressPools/useAddressPoolsSWR'
import Spinner from '@/components/elements/Spinner'
import Pagination from '@/components/elements/Pagination'
import SearchBar from '@/components/admin/SearchBar'
import { Link } from 'react-router-dom'

const IpamContainer = () => {
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.addressPools.index')
    const [page, setPage] = usePagination()
    const [isCreating, setIsCreating] = useState(false)

    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data } = useAddressPoolsSWR({ page, query: debouncedQuery })

    const columns: ColumnArray<AddressPool> = [
        {
            header: tStrings('name'),
            accessor: 'name',
            cell: ({ value, row }) => (
                <Link to={`/admin/ipam/${row.id}`} className='link text-foreground'>
                    {value}
                </Link>
            ),
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
