import RFB from '@novnc/novnc'
import { IconArrowsMaximize, IconRefresh, IconSend } from '@tabler/icons-react'
import { FitAddon } from '@xterm/addon-fit'
import { Terminal } from '@xterm/xterm'
import '@xterm/xterm/css/xterm.css'
import { useCallback, useEffect, useRef, useState } from 'react'
import { toast } from 'sonner'

import { Button } from '@/components/ui/Button'
import Spinner from '@/components/ui/Spinner.tsx'
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/Tooltip'

import { type ConsoleType, createConsoleSession } from './api'

const protocols = (token: string) => ['anchor.v1', `anchor.session.${token}`]

export default function ConsoleView({
    serverUuid,
    type,
}: {
    serverUuid: string
    type: ConsoleType
}) {
    const target = useRef<HTMLDivElement>(null)
    const connection = useRef<RFB | WebSocket | null>(null)
    const [attempt, setAttempt] = useState(0)
    const [connected, setConnected] = useState(false)

    useEffect(() => {
        let disposed = false
        let terminal: Terminal | undefined
        let fit: FitAddon | undefined

        const connect = async () => {
            setConnected(false)
            const session = await createConsoleSession(serverUuid, type)
            if (disposed || !target.current) return
            target.current.replaceChildren()

            if (type === 'novnc') {
                const rfb = new RFB(target.current, session.url, {
                    wsProtocols: protocols(session.token),
                })
                rfb.scaleViewport = true
                rfb.resizeSession = true
                rfb.background = 'rgb(9 9 11)'
                rfb.addEventListener('credentialsrequired', event => {
                    if (
                        event.detail.types.includes('password') &&
                        session.password
                    ) {
                        rfb.sendCredentials({
                            password: session.password,
                            username: '',
                            target: '',
                        })
                    }
                })
                rfb.addEventListener('connect', () => setConnected(true))
                rfb.addEventListener('disconnect', event => {
                    setConnected(false)
                    if (!event.detail.clean && !disposed)
                        toast.error('Console disconnected')
                })
                connection.current = rfb
            } else {
                terminal = new Terminal({
                    cursorBlink: true,
                    convertEol: true,
                    fontFamily:
                        'ui-monospace, SFMono-Regular, Menlo, monospace',
                    theme: { background: '#09090b' },
                })
                fit = new FitAddon()
                terminal.loadAddon(fit)
                terminal.open(target.current)
                fit.fit()
                const socket = new WebSocket(
                    session.url,
                    protocols(session.token)
                )
                socket.binaryType = 'arraybuffer'
                socket.onopen = () => setConnected(true)
                socket.onmessage = event =>
                    terminal?.write(
                        event.data instanceof ArrayBuffer
                            ? new Uint8Array(event.data)
                            : event.data
                    )
                socket.onclose = () => setConnected(false)
                terminal.onData(
                    data =>
                        socket.readyState === WebSocket.OPEN &&
                        socket.send(data)
                )
                connection.current = socket
            }
        }
        connect().catch(() => toast.error('Unable to open console'))
        const resize = () => fit?.fit()
        window.addEventListener('resize', resize)
        return () => {
            disposed = true
            window.removeEventListener('resize', resize)
            if (connection.current instanceof WebSocket)
                connection.current.close()
            else connection.current?.disconnect()
            terminal?.dispose()
            connection.current = null
        }
        // Deliberately depends only on what should actually re-open the console.
        // A mutation hook's mutateAsync is not referentially stable across the
        // idle -> pending -> success transitions it triggers itself, so having it
        // here tore the socket down and reconnected in a loop.
    }, [attempt, serverUuid, type])

    const sendCtrlAltDel = useCallback(() => {
        if (!(connection.current instanceof WebSocket))
            connection.current?.sendCtrlAltDel()
    }, [])

    return (
        <div className='relative h-full min-h-0 bg-zinc-950'>
            {!connected && (
                <div className='absolute inset-0 z-10 grid place-items-center bg-zinc-950'>
                    <Spinner className='size-6 text-zinc-300' />
                </div>
            )}
            <div
                ref={target}
                className='h-full w-full overflow-hidden [&_.xterm]:h-full [&_.xterm-viewport]:!overflow-y-auto [&_canvas]:mx-auto'
            />
            <div className='absolute right-3 bottom-3 z-20 flex gap-1 rounded-md border border-white/10 bg-zinc-900/90 p-1 shadow-lg'>
                {type === 'novnc' && (
                    <ToolButton
                        label='Send Ctrl+Alt+Delete'
                        onClick={sendCtrlAltDel}
                    >
                        <IconSend />
                    </ToolButton>
                )}
                <ToolButton
                    label='Reconnect'
                    onClick={() => setAttempt(value => value + 1)}
                >
                    <IconRefresh />
                </ToolButton>
                <ToolButton
                    label='Fullscreen'
                    onClick={() => document.documentElement.requestFullscreen()}
                >
                    <IconArrowsMaximize />
                </ToolButton>
            </div>
        </div>
    )
}

function ToolButton({
    label,
    onClick,
    children,
}: {
    label: string
    onClick: () => void
    children: React.ReactNode
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    size='icon'
                    variant='ghost'
                    className='text-zinc-200 hover:bg-zinc-800 hover:text-white'
                    onClick={onClick}
                >
                    {children}
                </Button>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    )
}
