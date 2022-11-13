import getBackups from '@/api/server/backups/getBackups';
import Display from '@/components/elements/displays/DisplayRow'
import Spinner from '@/components/elements/Spinner';
import ServerContentBlock from '@/components/servers/ServerContentBlock'
import { ServerContext } from '@/state/server';
import { bytesToString } from '@/util/helpers';
import { format, formatDistanceToNow } from 'date-fns';
import useSWR from 'swr'

const BackupsContainer = () => {
  const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
  const { data } = useSWR(['server:backups', uuid], () => getBackups(uuid, {}))

  return (
    <ServerContentBlock title='Backups'>
      { !data ? <Spinner /> :
      <Display.Group>
        {data.items.map((backup) => (
        <Display.Row key={backup.uuid} className='grid-cols-1 md:grid-cols-8 text-sm'>
          <div className='md:col-span-5'>
            <p className='overflow-hidden text-ellipsis font-semibold text-foreground'>{backup.name}</p>
          </div>
          <div>{ bytesToString(backup.size) }</div>
          <div className='md:col-span-2'>{ formatDistanceToNow(backup.createdAt, { includeSeconds: true, addSuffix: true }) }</div>
        </Display.Row>))}
      </Display.Group>}
    </ServerContentBlock>
  )
}

export default BackupsContainer
