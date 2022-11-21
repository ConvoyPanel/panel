import getBackups, { BackupResponse } from '@/api/server/backups/getBackups'
import Display from '@/components/elements/displays/DisplayRow'
import Spinner from '@/components/elements/Spinner'
import ServerContentBlock from '@/components/servers/ServerContentBlock'
import { ServerContext } from '@/state/server'
import useSWR from 'swr'
import Pagination from '@/components/elements/Pagination'
import usePagination from '@/util/usePagination'
import BackupRow from '@/components/servers/backups/BackupRow'
import CreateBackupButton from '@/components/servers/backups/CreateBackupButton'
import Menu from '@/components/elements/Menu'

const BackupsContainer = () => {
  const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid)
  const backupLimit = ServerContext.useStoreState((state) => state.server.data!.limits.backups)

  const [page, setPage] = usePagination()

  const { data, mutate } = useSWR(['server:backups', uuid, page], () =>
    getBackups(uuid, {
      page,
    })
  )

  return (
    <ServerContentBlock title='Backups'>
      <Menu />
      <CreateBackupButton swr={{ mutate }} />
      {!data ? (
        <Spinner />
      ) : data.pagination.total === 0 ? (
        <p className='text-sm text-center'>There are no backups</p>
      ) : (
        <Pagination data={data} onPageSelect={setPage}>
          {({ items }) => (
            <Display.Group>
              {items.map((backup) => (
                <BackupRow key={backup.uuid} backup={backup} />
              ))}
            </Display.Group>
          )}
        </Pagination>
      )}
    </ServerContentBlock>
  )
}

export default BackupsContainer
