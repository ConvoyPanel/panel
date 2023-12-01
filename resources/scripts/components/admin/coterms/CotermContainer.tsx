import usePagination from '@/util/usePagination'
import { LockClosedIcon, LockOpenIcon } from '@heroicons/react/20/solid'
import { useDebouncedValue } from '@mantine/hooks'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'

import { Coterm } from '@/api/admin/coterms/getCoterms'
import useCotermsSWR from '@/api/admin/coterms/useCotermsSWR'

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
import CotermResetModal from '@/components/admin/coterms/CotermResetModal'
import CotermTokenModal from '@/components/admin/coterms/CotermTokenModal'
import CreateCotermModal from '@/components/admin/coterms/CreateCotermModal'
import DeleteCotermModal from '@/components/admin/coterms/DeleteCotermModal'
import EditCotermModal from '@/components/admin/coterms/EditCotermModal'


const CotermContainer = () => {
    const { t: tStrings } = useTranslation('strings')
    const [isCreating, setIsCreating] = useState(false)
    const [cotermToEdit, setCotermToEdit] = useState<Coterm | null>(null)
    const [cotermToReset, setCotermToReset] = useState<Coterm | null>(null)
    const [cotermToDelete, setCotermToDelete] = useState<Coterm | null>(null)
    const [cotermToken, setCotermToken] = useState<string | null>(null)
    const [page, setPage] = usePagination()

    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, mutate } = useCotermsSWR({ page, query: debouncedQuery })

    const columns: ColumnArray<Coterm> = [
        {
            accessor: 'name',
            header: tStrings('name'),
            cell: ({ value, row }) => (
                <Link
                    to={`/admin/coterms/${row.id}`}
                    className='link text-foreground'
                >
                    {value}
                </Link>
            ),
        },
        {
            accessor: 'fqdn',
            header: tStrings('fqdn'),
        },
        {
            accessor: 'port',
            header: tStrings('port'),
            align: 'center',
        },
        {
            accessor: 'isTlsEnabled',
            header: 'TLS',
            align: 'center',
            cell: ({ value }) => (
                <div className='grid place-items-center'>
                    {value ? (
                        <LockClosedIcon
                            title='hidden'
                            className='h-5 w-5 text-foreground'
                        />
                    ) : (
                        <LockOpenIcon
                            title='hidden'
                            className='h-5 w-5 text-error'
                        />
                    )}
                </div>
            ),
        },
        {
            accessor: 'nodesCount',
            header: tStrings('node_other'),
            align: 'center',
        },
    ]

    const rowActions = ({ row: coterm }: RowActionsProps<Coterm>) => {
        return (
            <Actions>
                <Menu.Item onClick={() => setCotermToEdit(coterm)}>
                    {tStrings('edit')}
                </Menu.Item>
                <Menu.Item onClick={() => setCotermToReset(coterm)}>
                    Reset
                </Menu.Item>
                <Menu.Divider />
                <Menu.Item
                    color='red'
                    onClick={() => setCotermToDelete(coterm)}
                >
                    {tStrings('delete')}
                </Menu.Item>
            </Actions>
        )
    }

    const handleTokenModalClose = () => {
        setCotermToken(null)
        setCotermToReset(null)
    }

    return (
        <div className='bg-background min-h-screen'>
            <PageContentBlock title={'Coterm Instances'}>
                <CreateCotermModal
                    open={isCreating}
                    onClose={() => setIsCreating(false)}
                    mutate={mutate}
                />
                <EditCotermModal
                    coterm={cotermToEdit}
                    onClose={() => setCotermToEdit(null)}
                    mutate={mutate}
                />
                <CotermResetModal
                    coterm={cotermToReset}
                    onReset={token => setCotermToken(token)}
                    onClose={() => setCotermToReset(null)}
                />
                <CotermTokenModal
                    value={cotermToken}
                    onClose={handleTokenModalClose}
                />
                <DeleteCotermModal
                    coterm={cotermToDelete}
                    onClose={() => setCotermToDelete(null)}
                    mutate={mutate}
                />

                <SearchBar
                    value={query}
                    onChange={e => setQuery(e.target.value)}
                    buttonText={'Create Instance'}
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

export default CotermContainer