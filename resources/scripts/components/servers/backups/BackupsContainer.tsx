import { ServerContext } from '@/state/server'
import usePagination from '@/util/usePagination'
import useSWR from 'swr'

import getBackups from '@/api/server/backups/getBackups'

import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import Display from '@/components/elements/displays/DisplayRow'

import ServerContentBlock from '@/components/servers/ServerContentBlock'
import BackupRow from '@/components/servers/backups/BackupRow'
import CreateBackupButton from '@/components/servers/backups/CreateBackupButton'

const BackupsContainer = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)

    const [page, setPage] = usePagination()

    const { data, mutate } = useSWR(
        ['server:backups', uuid, page],
        () =>
            getBackups(uuid, {
                page,
            }),
        {
            // A backup runs as a queued job and the list only learned it had
            // finished on a reload. Poll while one is still running.
            refreshInterval: latest =>
                latest?.items.some(backup => !backup.completedAt) ? 5000 : 0,
        }
    )

    return (
        <ServerContentBlock showFlashKey='backups' title='Backups'>
            <CreateBackupButton
                backupCount={data?.backupCount}
                swr={{ mutate }}
            />
            {!data ? (
                <Spinner />
            ) : data.backupCount === 0 ? (
                <p className='text-sm text-center'>There are no backups</p>
            ) : (
                <Pagination data={data} onPageSelect={setPage}>
                    {({ items }) => (
                        <Display.Group>
                            {items.map(backup => (
                                <BackupRow
                                    swr={{ mutate }}
                                    key={backup.uuid}
                                    backup={backup}
                                />
                            ))}
                        </Display.Group>
                    )}
                </Pagination>
            )}
        </ServerContentBlock>
    )
}

export default BackupsContainer
