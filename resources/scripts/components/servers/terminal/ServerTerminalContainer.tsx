import getTerminalToken from '@/api/server/getTerminalToken'
import ServerContentBlock from '@/components/servers/ServerContentBlock'
import { ServerContext } from '@/state/server'
import useFlash from '@/util/useFlash'
import { Loader } from '@mantine/core'
import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'

const ServerTerminalContainer = () => {
  const { clearFlashes, clearAndAddHttpError } = useFlash()
  const [params] = useSearchParams()
  const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid)
  const [message, setMessage] = useState('Initializing')
  const messages = ['Checking remote health', 'Generating token', 'Connecting']

  useEffect(() => {
    // select a message but pause at random intervals
    messages.forEach((message) => {
      setTimeout(() => {
        setMessage(message)
      }, Math.random() * 3000)
    })


    getTerminalToken(uuid).then((data) => {
      // @ts-expect-error
      window.location = `https://${data.fqdn}:${
        data.port
      }/novnc/novnc.html?console=qemu&virtualization=qemu&node=${encodeURIComponent(
        data.node
      )}&vmid=${data.vmid}&token=${encodeURIComponent(data.token)}${params.get('type') === 'xterm' ? '&xtermjs=1' : ''}`
    }).catch(error => {
        clearAndAddHttpError({ key: 'terminal', error })
    })
  }, [])

  return (
    <ServerContentBlock title='Terminal' showFlashKey='terminal'>
      <div className='grid place-items-center w-full mt-20'>
        <div className='flex flex-col items-center'>
          <h6 className='h6'>Starting Terminal</h6>
          <p className='description-small'>{message}</p>
          <Loader className='mt-3' size='lg' />
        </div>
      </div>
    </ServerContentBlock>
  )
}

export default ServerTerminalContainer
