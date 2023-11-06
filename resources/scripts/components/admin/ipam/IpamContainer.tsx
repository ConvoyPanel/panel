import usePagination from '@/util/usePagination'
import { useDebouncedValue } from '@mantine/hooks'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'

import { AddressPool } from '@/api/admin/addressPools/getAddressPools'
import useAddressPoolsSWR from '@/api/admin/addressPools/useAddressPoolsSWR'

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
import CreatePoolModal from '@/components/admin/ipam/CreatePoolModal'
import DeletePoolModal from '@/components/admin/ipam/DeletePoolModal'
import EditPoolModal from '@/components/admin/ipam/EditPoolModal'


const IpamContainer = () => {
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.addressPools.index')
    const [page, setPage] = usePagination()
    const [isCreating, setIsCreating] = useState(false)
    const [poolToDelete, setPoolToDelete] = useState<AddressPool | null>(null)
    const [poolToEdit, setPoolToEdit] = useState<AddressPool | null>(null)

    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, mutate } = useAddressPoolsSWR({ page, query: debouncedQuery })

    const columns: ColumnArray<AddressPool> = [
        {
            header: tStrings('name'),
            accessor: 'name',
            cell: ({ value, row }) => (
                <Link
                    to={`/admin/ipam/${row.id}`}
                    className='link text-foreground'
                >
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

    const rowActions = ({ row: pool }: RowActionsProps<AddressPool>) => {
        return (
            <Actions>
                <Menu.Item onClick={() => setPoolToEdit(pool)}>
                    {tStrings('edit')}
                </Menu.Item>
                <Menu.Divider />
                <Menu.Item color='red' onClick={() => setPoolToDelete(pool)}>
                    {tStrings('delete')}
                </Menu.Item>
            </Actions>
        )
    }

    return (
        <div className='bg-background min-h-screen'>
            <PageContentBlock title={tStrings('ipam') ?? ''}>
                <CreatePoolModal
                    open={isCreating}
                    onClose={() => setIsCreating(false)}
                    mutate={mutate}
                />
                <DeletePoolModal
                    pool={poolToDelete}
                    onClose={() => setPoolToDelete(null)}
                    mutate={mutate}
                />
                <EditPoolModal
                    pool={poolToEdit}
                    onClose={() => setPoolToEdit(null)}
                    mutate={mutate}
                />
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
        </div>
    )
}

export default IpamContainer