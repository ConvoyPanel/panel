import PoolContentBlock from '@/components/admin/ipam/PoolContentBlock'
import { useTranslation } from 'react-i18next'
import SearchBar from '@/components/admin/SearchBar'
import { useState } from 'react'
import { useDebouncedValue } from '@mantine/hooks'
import useAddressesSWR from '@/api/admin/addressPools/useAddressesSWR'
import usePagination from '@/util/usePagination'
import Table, { Actions, ColumnArray, RowActionsProps } from '@/components/elements/displays/Table'
import { Link } from 'react-router-dom'
import { Address } from '@/api/server/getServer'
import Spinner from '@/components/elements/Spinner'
import Pagination from '@/components/elements/Pagination'
import Breadcrumbs from '@/components/elements/Breadcrumbs'
import CreateAddressModal from '@/components/admin/ipam/addresses/CreateAddressModal'
import { AddressPool } from '@/api/admin/addressPools/getAddressPools'
import Menu from '@/components/elements/Menu'
import EditAddressModal from '@/components/admin/ipam/addresses/EditAddressModal'
import DeleteAddressModal from '@/components/admin/ipam/addresses/DeleteAddressModal'

const AddressesContainer = () => {
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.addressPools.addresses')
    const [isCreating, setIsCreating] = useState(false)
    const [addressToEdit, setAddressToEdit] = useState<Address | null>(null)
    const [addressToDelete, setAddressToDelete] = useState<Address | null>(null)

    const [query, setQuery] = useState('')
    const [page, setPage] = usePagination()
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, mutate } = useAddressesSWR({ page, query: debouncedQuery, include: ['server'] })

    const columns: ColumnArray<Address> = [
        {
            header: tStrings('address'),
            accessor: 'address',
        },
        {
            header: tStrings('cidr'),
            accessor: 'cidr',
        },
        {
            header: tStrings('gateway'),
            accessor: 'gateway',
        },
        {
            header: tStrings('mac_address'),
            accessor: 'macAddress',
        },
        {
            header: tStrings('type'),
            accessor: 'type',
        },
        {
            header: tStrings('server'),
            accessor: 'server',
            cell: ({ value }) =>
                value ? (
                    <Link to={`/admin/servers/${value.uuidShort}`} className='link text-foreground'>
                        {value.hostname}
                    </Link>
                ) : null,
        },
    ]

    const rowActions = ({ row: address }: RowActionsProps<Address>) => {
        return (
            <Actions>
                <Menu.Item onClick={() => setAddressToEdit(address)}>{tStrings('edit')}</Menu.Item>
                <Menu.Divider />
                <Menu.Item color='red' onClick={() => setAddressToDelete(address)}>
                    {tStrings('delete')}
                </Menu.Item>
            </Actions>
        )
    }

    return (
        <div className={'bg-background min-h-screen'}>
            <PoolContentBlock title={tStrings('address_other') ?? 'Addresses'}>
                <CreateAddressModal open={isCreating} onClose={() => setIsCreating(false)} mutate={mutate} />
                <EditAddressModal address={addressToEdit} onClose={() => setAddressToEdit(null)} mutate={mutate} />
                <DeleteAddressModal
                    address={addressToDelete}
                    onClose={() => setAddressToDelete(null)}
                    mutate={mutate}
                />
                <SearchBar
                    value={query}
                    onChange={e => setQuery(e.target.value)}
                    buttonText={t('create_address')}
                    onClick={() => setIsCreating(true)}
                />
                {!data ? (
                    <Spinner />
                ) : (
                    <Pagination data={data} onPageSelect={setPage}>
                        {({ items }) => <Table columns={columns} data={items} rowActions={rowActions} />}
                    </Pagination>
                )}
            </PoolContentBlock>
        </div>
    )
}

export default AddressesContainer
