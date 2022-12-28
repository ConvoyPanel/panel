import updateStatus, { PowerAction } from '@/api/server/updateStatus'
import Button from '@/components/elements/Button'
import { ServerContext } from '@/state/server'
import useNotify from '@/util/useNotify'

const ServerPowerBlock = () => {
  const uuid = ServerContext.useStoreState((state) => state.server.data?.uuid)
  const state = ServerContext.useStoreState((state) => state.status.data?.state)
  const notify = useNotify()

  const update = (state: PowerAction) => {
    updateStatus(uuid!, state)
        .then(() => notify({ title: 'Success', message: 'Server state updated.', color: 'green' }))
        .catch(() => notify({ title: 'Error', message: 'Failed to update server state.', color: 'red' }))
  }

  return (
    <div className='grid grid-cols-2 gap-3 xs:gap-0 xs:flex justify-end xs:space-x-3 mb-3'>
      <Button
        className='transition-colors'
        disabled={!state || state === 'running'}
        onClick={() => update('start')}
      >
        Start
      </Button>
      <Button
        className='transition-colors'
        disabled={state !== 'running'}
        onClick={() => update('restart')}
      >
        Restart
      </Button>
      <Button
        className='transition-colors'
        color='danger'
        variant='outline'
        disabled={!state || state === 'stopped'}
        onClick={() => update('kill')}
      >
        Kill
      </Button>
      <Button
        className='transition-colors'
        color='danger'
        variant='filled'
        disabled={state !== 'running'}
        onClick={() => update('shutdown')}
      >
        Shutdown
      </Button>
    </div>
  )
}

export default ServerPowerBlock
