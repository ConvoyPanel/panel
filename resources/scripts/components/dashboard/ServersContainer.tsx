import { useStoreState } from '@/state'
import { usePersistedState } from '@/util/usePersistedState'
import { MagnifyingGlassIcon } from '@heroicons/react/20/solid'
import { Skeleton, Switch } from '@mantine/core'
import { useDebouncedValue } from '@mantine/hooks'
import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useLocation } from 'react-router-dom'
import useSWR from 'swr'

import getServers from '@/api/getServers'

import Pagination from '@/components/elements/Pagination'
import TextInput from '@/components/elements/inputs/TextInput'

import ServerCard from '@/components/dashboard/ServerCard'

const ServerContainer = () => {
    const { t } = useTranslation('dashboard.index')
    const { t: tStrings } = useTranslation('strings')
    const { search: location } = useLocation()
    const defaultPage = Number(new URLSearchParams(location).get('page') || '1')
    const [page, setPage] = useState(
        !isNaN(defaultPage) && defaultPage > 0 ? defaultPage : 1
    )
    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)

    const uuid = useStoreState(state => state.user.data!.email)
    const rootAdmin = useStoreState(state => state.user.data!.rootAdmin)
    const [showOnlyAdmin, setShowOnlyAdmin] = usePersistedState(
        `${uuid}:show_all_servers`,
        false
    )
    const { data } = useSWR(
        [
            '/api/client/servers',
            showOnlyAdmin && rootAdmin,
            page,
            debouncedQuery,
        ],
        () =>
            getServers({
                query: debouncedQuery,
                page,
                type: showOnlyAdmin && rootAdmin ? 'all' : undefined,
                perPage: 51,
            })
    )

    useEffect(() => {
        setPage(1)
    }, [debouncedQuery])

    return (
        <>
            {rootAdmin && (
                <div className='flex space-x-3 justify-end mb-3'>
                    <p className='description-small'>{t('show_all_servers')}</p>
                    <Switch
                        checked={showOnlyAdmin}
                        onChange={() => setShowOnlyAdmin(!showOnlyAdmin)}
                    />
                </div>
            )}
            <TextInput
                icon={
                    <MagnifyingGlassIcon className='text-accent-400 w-4 h-4' />
                }
                value={query}
                onChange={e => setQuery(e.currentTarget.value)}
                placeholder={`${tStrings('search')}...`}
            />
            <div className='pt-6'>
                {!data ? (
                    <div className='grid sm:grid-cols-2 lg:grid-cols-3 gap-6'>
                        {[1, 2, 3, 4, 5, 6].map(val => (
                            <Skeleton key={val} height='136px' />
                        ))}
                    </div>
                ) : data.pagination.total === 0 ? (
                    <p className='text-sm text-center'>
                        {showOnlyAdmin
                            ? t('no_servers_admin_empty_state')
                            : t('no_servers_empty_state')}
                    </p>
                ) : (
                    <Pagination data={data} onPageSelect={setPage}>
                        {({ items }) => (
                            <div className='grid sm:grid-cols-2 lg:grid-cols-3 gap-6'>
                                {items.map(server => (
                                    <ServerCard
                                        key={server.uuid}
                                        server={server}
                                    />
                                ))}
                            </div>
                        )}
                    </Pagination>
                )}
            </div>
        </>
    )
}

export default ServerContainer
