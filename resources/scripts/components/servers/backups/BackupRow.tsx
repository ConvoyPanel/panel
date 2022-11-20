import { Backup } from '@/api/server/backups/getBackups'
import Display from '@/components/elements/displays/DisplayRow'
import { bytesToString } from '@/util/helpers'
import { formatDistanceToNow } from 'date-fns'

interface Props {
  backup: Backup
}

const BackupRow = ({ backup }: Props) => {
  return (
    <Display.Row
      key={backup.uuid}
      className='grid-cols-1 md:grid-cols-8 text-sm'
    >
      <div className='md:col-span-5'>
        <p className='overflow-hidden text-ellipsis font-semibold text-foreground'>
          {backup.name}
        </p>
      </div>
      <div>{bytesToString(backup.size)}</div>
      <div className='md:col-span-2'>
        {formatDistanceToNow(backup.createdAt, {
          includeSeconds: true,
          addSuffix: true,
        })}
      </div>
    </Display.Row>
  )
}

export default BackupRow
