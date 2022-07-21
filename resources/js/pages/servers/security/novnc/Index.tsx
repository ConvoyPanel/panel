import { CloudinitConfig } from '@/api/server/settings/types'
import getCredentials, { VncCredentials } from '@/api/server/vnc/getCredentials'
import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/types/server'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerNav from '@/components/servers/ServerNav'
import PasswordConfigSettings from '@/pages/servers/security/modules/PasswordConfigSettings'
import { SettingsContext } from '@/pages/servers/settings/Index'
import { CheckCircleIcon, CheckIcon, ClockIcon } from '@heroicons/react/outline'
import { Head } from '@inertiajs/inertia-react'
import { Button, Loader } from '@mantine/core'
import { useEffect, useMemo, useState } from 'react'

interface Props extends DefaultProps {
  server: Server
}

const Index = ({ auth, server }: Props) => {
  const [processing, setProcessing] = useState(true)
  const [credentials, setCredentials] = useState<VncCredentials>()
  const [used, setUsed] = useState(false)

  useEffect(() => {
    const main = async () => {
      const { data } = await getCredentials(server.id)

      setCredentials(data)

      setProcessing(false)
    }
    main()
  }, [])

  const launchPopup = () => {
    if (!credentials) return

    window.open(
      `${credentials.endpoint}?novnc=1&token=${encodeURIComponent(
        credentials.token
      )}&console=qemu&virtualization=qemu&node=${encodeURIComponent(
        credentials.node_id
      )}&vmid=${credentials.vmid}`,
      'Server Terminal | Convoy',
      'width=800,height=600,resizable=yes,scrollbars=yes'
    )

    setUsed(true)
  }

  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - NoVNC`} />

      <Main>
        <h3 className='h3-deemphasized'>NoVNC Terminal</h3>
        <div className='grid place-items-center w-full h-[30vh]'>
          <div className='flex flex-col items-center space-y-3'>
            {!processing && (
              <>
                {!used && (
                  <>
                    <CheckCircleIcon className='text-green-600 w-14 h-14' />
                    <h3 className='h3-deemphasized'>Session Authorized</h3>
                  </>
                )}
                {used && <>
                  <>
                    <ClockIcon className='text-yellow-600 w-14 h-14' />
                    <h3 className='h3-deemphasized'>Session Active</h3>
                  </>
                </>}
                <Button disabled={used} onClick={launchPopup}>
                  Launch noVNC in popup
                </Button>
              </>
            )}
            {processing && (
              <>
                <Loader />
                <h3 className='h3-deemphasized'>Connecting</h3>
              </>
            )}
          </div>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Index
