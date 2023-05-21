import createConsoleSession from '@/api/server/createConsoleSession'
import ServerContentBlock from '@/components/servers/ServerContentBlock'
import { ServerContext } from '@/state/server'
import useFlash, { useFlashKey } from '@/util/useFlash'
import { Loader } from '@mantine/core'
import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'

const ServerTerminalContainer = () => {
    const [params] = useSearchParams()
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(`servers.${uuid}.console`)
    const [message, setMessage] = useState('Initializing')
    const messages = ['Checking remote health', 'Generating token', 'Connecting']

    useEffect(() => {
        // select a message but pause at random intervals
        messages.forEach(message => {
            setTimeout(() => {
                setMessage(message)
            }, Math.random() * 3000)
        })

        const main = async () => {
            try {
                const creds = await createConsoleSession(uuid)

                if ('ticket' in creds) {
                    window.location.href = `https://${creds.fqdn}:${
                        creds.port
                    }/novnc/novnc.html?console=qemu&virtualization=qemu&node=${encodeURIComponent(creds.node)}&vmid=${
                        creds.vmid
                    }&token=${encodeURIComponent(creds.ticket)}${params.get('type') === 'xterm_js' ? '&xtermjs=1' : ''}`
                } else {
                    window.location.href = `${creds.isTlsEnabled ? 'https' : 'http'}://${
                        creds.fqdn
                    }:${creds.port}/?type=${encodeURIComponent(params.get('type')!)}&token=${encodeURIComponent(creds.token)}`
                }
            } catch (e) {
                clearAndAddHttpError(e as Error)
            }
        }

        main()
    }, [])

    return (
        <ServerContentBlock title='Terminal' showFlashKey={`servers.${uuid}.console`}>
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
