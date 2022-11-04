import getServers from '@/api/getServers'
import ServerCard from '@/components/dashboard/ServerCard'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import { useStoreState } from '@/state'
import { usePersistedState } from '@/util/usePersistedState'
import { MagnifyingGlassIcon } from '@heroicons/react/20/solid'
import { Switch, TextInput } from '@mantine/core'
import { useCallback, useEffect, useState } from 'react'
import { useLocation } from 'react-router-dom'
import useSWR, { mutate } from 'swr'
import debounce from 'debounce'

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
    () => getServers({ query: query.length > 0 ? query : undefined ,page, type: showOnlyAdmin && rootAdmin ? 'all' : undefined })
  )

  useEffect(() => {
      // Don't use react-router to handle changing this part of the URL, otherwise it
      // triggers a needless re-render. We just want to track this in the URL incase the
      // user refreshes the page.
      window.history.replaceState(null, document.title, `/${page <= 1 ? '' : `?page=${page}`}`);
  }, [page]);

  const search = useCallback(debounce(() => {
    setPage(1)
    mutate()
  }, 500), [])

  return (
    <>
      <div className='flex space-x-3 items-center justify-end'>
        <p className='description-small'>Show all servers</p>
        <Switch checked={showOnlyAdmin} onChange={() => setShowOnlyAdmin(!showOnlyAdmin)} />
      </div>
      <TextInput
        icon={<MagnifyingGlassIcon className='w-4 h-4' />}
        value={query}
        onChange={(e) => { setQuery(e.currentTarget.value); search()}}
        placeholder='Search...'
      />
      {!data ? (
        <Spinner />
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
    </>
  )
}

export default ServerContainer
