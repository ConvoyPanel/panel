import getServers from '@/api/getServers'
import ServerCard from '@/components/dashboard/ServerCard'
import Pagination from '@/components/elements/Pagination'
import { useStoreState } from '@/state'
import { usePersistedState } from '@/util/usePersistedState'
import { MagnifyingGlassIcon } from '@heroicons/react/20/solid'
import { Skeleton, Switch } from '@mantine/core'
import { useCallback, useEffect, useState } from 'react'
import { useLocation } from 'react-router-dom'
import useSWR from 'swr'
import debounce from 'debounce'
import TextInput from '@/components/elements/inputs/TextInput'

const ServerContainer = () => {
  const { search: location } = useLocation()
  const defaultPage = Number(new URLSearchParams(location).get('page') || '1')
  const [page, setPage] = useState(
    !isNaN(defaultPage) && defaultPage > 0 ? defaultPage : 1
  )
  const [query, setQuery] = useState('')

  const uuid = useStoreState((state) => state.user.data!.email)
  const rootAdmin = useStoreState((state) => state.user.data!.rootAdmin)
  const [showOnlyAdmin, setShowOnlyAdmin] = usePersistedState(
    `${uuid}:show_all_servers`,
    false
  )
  const { data, mutate } = useSWR(
    ['/api/client/servers', showOnlyAdmin && rootAdmin, page],
    () =>
      getServers({
        query: query.length > 0 ? query : undefined,
        page,
        type: showOnlyAdmin && rootAdmin ? 'all' : undefined,
        perPage: 51,
      })
  )

  useEffect(() => {
    // Don't use react-router to handle changing this part of the URL, otherwise it
    // triggers a needless re-render. We just want to track this in the URL incase the
    // user refreshes the page.
    window.history.replaceState(
      null,
      document.title,
      `/${page <= 1 ? '' : `?page=${page}`}`
    )
  }, [page])

  const search = useCallback(
    debounce(() => {
      setPage(1)
      mutate()
    }, 500),
    []
  )

  return (
    <>
      {rootAdmin && (
        <div className='flex space-x-3 items-center justify-end mb-3'>
          <p className='description-small'>Show all servers</p>
          <Switch
            checked={showOnlyAdmin}
            onChange={() => setShowOnlyAdmin(!showOnlyAdmin)}
          />
        </div>
      )}
      <TextInput
        icon={<MagnifyingGlassIcon className='text-accent-400 w-4 h-4' />}
        value={query}
        onChange={(e) => {
          setQuery(e.currentTarget.value)
          search()
        }}
        placeholder='Search...'
      />
      <div className='pt-6'>
        {!data ? (
          <div className='grid sm:grid-cols-2 lg:grid-cols-3 gap-6'>
            {[1, 2, 3, 4, 5, 6].map((val) => (
              <Skeleton key={val} height='136px' />
            ))}
          </div>
        ) : (
          <Pagination data={data} onPageSelect={setPage}>
            {({ items }) => (
              <div className='grid sm:grid-cols-2 lg:grid-cols-3 gap-6'>
                {items.map((server) => (
                  <ServerCard key={server.uuid} server={server} />
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
