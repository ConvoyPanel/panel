import { useServer } from '@/features/servers/detail/api.ts'
import useClipboard from '@/hooks/use-clipboard.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { cn } from '@/utils'
import RFB from '@novnc/novnc'
import {
    IconArrowLeft,
    IconArrowsMaximize,
    IconArrowsMinimize,
    IconDeviceDesktop,
    IconKeyboard,
    IconRefresh,
    IconZoomIn,
} from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { FitAddon } from '@xterm/addon-fit'
import { Terminal } from '@xterm/xterm'
import '@xterm/xterm/css/xterm.css'
import { AxiosError } from 'axios'
import { useCallback, useEffect, useRef, useState } from 'react'

import { Button, buttonVariants } from '@/components/ui/Button'
import Spinner from '@/components/ui/Spinner.tsx'
import { toast } from '@/components/ui/Toast'
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/Tooltip'

import {
    type ConsoleType,
    type DisplayConsole,
    createConsoleSession,
    enableDisplayConsole,
    useDisplayConsole,
} from './api'

const protocols = (token: string) => ['anchor.v1', `anchor.session.${token}`]

// The panel refuses a session with a real reason ("Anchor … is not online with a
// compatible protocol version", "does not have an Anchor agent configured");
// throwing it away and saying "Unable to open console" is what made this feel
// like it fails at random.
const reason = (error: unknown) =>
    error instanceof AxiosError && error.response?.data?.message
        ? (error.response.data.message as string)
        : 'The console session could not be created.'

/**
 * What we can say when the console dies without explaining itself.
 *
 * `keepOutput` means the console printed something before it went: that output
 * is the most informative thing on the screen, so the notice must sit beside it
 * rather than cover it.
 */
type Failure = { message: string; code?: number; keepOutput?: boolean }

// Close codes worth translating. A console that exits on its own closes
// cleanly, so "clean" here means the process ended, not that all is well.
const closeCodes: Record<number, string> = {
    1000: 'the console process exited',
    1005: 'the console process exited without a status',
    1006: 'the connection dropped without a close frame',
    1011: 'the agent reported an internal error',
}

/**
 * Keys the browser and the host OS swallow before RFB ever sees them.
 *
 * Super opens the host's launcher, Alt+Tab switches host windows, Ctrl+W closes
 * the tab — so a guest can never receive them from a real keyboard. These are
 * sent explicitly instead, by X11 keysym.
 *
 * Modifiers are held rather than tapped: pressing one sends a key-down and
 * nothing else, so the next ordinary keystroke arrives combined with it, just
 * as if the key were physically down.
 */
const modifierKeys = [
    { label: 'Ctrl', keysym: 0xffe3, code: 'ControlLeft' },
    { label: 'Alt', keysym: 0xffe9, code: 'AltLeft' },
    { label: 'Shift', keysym: 0xffe1, code: 'ShiftLeft' },
    { label: 'Super', keysym: 0xffeb, code: 'MetaLeft' },
] as const

/**
 * Long pastes are thousands of round-trips, and a guest keyboard buffer will
 * drop what it cannot keep up with, so typing is paced and bounded.
 */
const typingDelayMs = 8
const typingLimit = 4096

/**
 * Replay text as key events, for the screens a clipboard cannot reach.
 *
 * An OS installer, GRUB or a BIOS has no guest agent — there is no OS yet — so
 * real clipboard sync is impossible there and this is the only thing that
 * works. It is strictly worse everywhere else: one-way, slow, and keysym-based,
 * so a guest on a non-US keymap will resolve symbols to different characters.
 */
const typeText = async (
    rfb: RFB,
    text: string,
    onProgress: (done: number) => void
) => {
    const characters = [...text.slice(0, typingLimit)]

    for (const [index, character] of characters.entries()) {
        if (character === '\n' || character === '\r') {
            rfb.sendKey(0xff0d, 'Enter')
        } else if (character === '\t') {
            rfb.sendKey(0xff09, 'Tab')
        } else {
            const point = character.codePointAt(0) ?? 0
            // Latin-1 maps onto keysyms one to one; everything above it uses
            // the Unicode keysym range.
            rfb.sendKey(point < 0x100 ? point : 0x01000000 + point, null)
        }

        onProgress(index + 1)
        await new Promise(resolve => setTimeout(resolve, typingDelayMs))
    }

    return characters.length
}

/** Sent as a full press and release, with whatever modifiers are held. */
const tapKeys = [
    { label: 'Esc', keysym: 0xff1b, code: 'Escape' },
    { label: 'Tab', keysym: 0xff09, code: 'Tab' },
    ...Array.from({ length: 12 }, (_, index) => ({
        label: `F${index + 1}`,
        keysym: 0xffbe + index,
        code: `F${index + 1}`,
    })),
]

// A serial console can only attach to a serial device the VM actually has, with
// something listening on it inside the guest. Both are invisible from here, so
// the terminal fails by going quiet — say what to check rather than spin.
const serialHint =
    'A serial console needs a serial device on the VM (serial0) and a login prompt bound to it inside the guest. The display console does not.'

// The display console has no equivalent prerequisite — every VM has a VGA
// device — so once the agent has accepted the session, all that is left is the
// RFB handshake, and noVNC keeps its own account of that.
const displayHint =
    'The agent accepted the session but the VNC handshake never finished, which usually means the one-time password was refused. noVNC logs the step it stopped on to the browser console.'

/** How a console's WebSocket ended, as far as the browser can tell. */
type Closure = { code: number; reason: string }

/** The address the browser itself has to reach, named so it can be checked. */
const agentHost = (url: string) => {
    try {
        return new URL(url).host
    } catch {
        return url
    }
}

/**
 * Whether the agent answers this browser at all.
 *
 * A WebSocket that never opens cannot say why: a refused upgrade, a certificate
 * the browser will not trust and a host that does not resolve all arrive as the
 * same empty failure. An ordinary request to the agent's health endpoint tells
 * them apart — the response is opaque without CORS, but whether one arrives is
 * the entire question.
 */
const reachable = async (url: string) => {
    try {
        const health = new URL(url)
        health.protocol = health.protocol === 'wss:' ? 'https:' : 'http:'
        health.pathname = '/health'
        health.search = ''
        await fetch(health, { mode: 'no-cors', cache: 'no-store' })

        return true
    } catch {
        return false
    }
}

const ConsoleView = ({
    serverUuid,
    type,
}: {
    serverUuid: string
    type: ConsoleType
}) => {
    const { data: server } = useServer(serverUuid)
    const target = useRef<HTMLDivElement>(null)
    const connection = useRef<RFB | WebSocket | null>(null)
    const [attempt, setAttempt] = useState(0)
    const [connected, setConnected] = useState(false)
    const [failure, setFailure] = useState<Failure | null>(null)
    const [held, setHeld] = useState<string[]>([])
    // Scale to fit by default; a low-resolution guest (a boot console, a BIOS)
    // stretches badly, so actual size stays one click away.
    const [fitDisplay, setFitDisplay] = useState(true)
    const [keysOpen, setKeysOpen] = useState(false)
    const [typing, setTyping] = useState<number | null>(null)
    // Held when the guest copies something but the browser refuses to write to
    // the clipboard without a gesture, so a button can finish the job.
    const [guestClipboard, setGuestClipboard] = useState('')
    const { copy } = useClipboard({ successMessage: 'Copied from the server' })
    // Reaches the node, so it is only asked once this console has actually
    // failed — a working console has already proven the answer.
    const { data: displayStatus } = useDisplayConsole(
        serverUuid,
        type === 'novnc' && failure !== null
    )
    const refreshDisplay = useQueryMutator<DisplayConsole>([
        'servers',
        serverUuid,
        'display-console',
    ])
    const { mutate: enableDisplay, isPending: enablingDisplay } = useMutation({
        mutationFn: () => enableDisplayConsole(serverUuid),
        onSuccess: status => refreshDisplay(() => status),
        onError: () =>
            toast.add({ title: 'Could not change the display', type: 'error' }),
    })
    useEffect(() => {
        let disposed = false
        let terminal: Terminal | undefined
        let fit: FitAddon | undefined
        // Whether the console ever actually carried anything. Distinguishes
        // "never came up" from "came up and then dropped", which need different
        // explanations — and neither may leave the spinner running.
        let established = false

        const fail = (value: Failure) => {
            if (!disposed) setFailure(value)
        }

        const connect = async () => {
            setConnected(false)
            setFailure(null)
            const session = await createConsoleSession(serverUuid, type)
            if (disposed || !target.current) return
            target.current.replaceChildren()

            const host = agentHost(session.url)

            // A browser refuses an insecure socket from a secure page before it
            // sends anything, so nothing further down would ever get to explain
            // it. This is a property of the Anchor's address, not a fault.
            if (
                window.location.protocol === 'https:' &&
                session.url.startsWith('ws:')
            ) {
                fail({
                    message: `The panel is served over HTTPS, so this browser will not open an insecure console connection to ${host}. Give this Anchor an https:// public URL.`,
                })

                return
            }

            // noVNC would open this itself, but it reports a disconnect as a
            // bare boolean, so the agent's close frame — the one thing that
            // says why a session died — never reaches the panel. Opening it
            // here and handing RFB the socket (noVNC accepts one in place of a
            // URL) keeps that diagnostic on the display console too.
            const socket = new WebSocket(session.url, protocols(session.token))
            socket.binaryType = 'arraybuffer'
            // Whether the upgrade ever succeeded, and what the peer said on the
            // way out. Added as listeners rather than as `on*` handlers, which
            // RFB assigns for itself when it attaches.
            let opened = false
            let closure: Closure | null = null
            socket.addEventListener('open', () => {
                opened = true
            })
            socket.addEventListener('close', event => {
                closure = { code: event.code, reason: event.reason.trim() }
            })

            /** Explain a console that never carried anything. */
            const failToStart = () => {
                // The agent ends every session with a close frame carrying its
                // own diagnostic — whatever `qm` reported, or which VM it could
                // not find. Where there is one, nothing we could infer beats it.
                if (closure?.reason) {
                    fail({ message: closure.reason, code: closure.code })

                    return
                }

                if (opened) {
                    fail({
                        message: type === 'novnc' ? displayHint : serialHint,
                        code: closure?.code,
                    })

                    return
                }

                // The socket never opened, so nothing on the node was ever even
                // asked about the VM: whatever is wrong sits between this
                // browser and the agent's address. The panel reaches the agent
                // over its own path and cannot vouch for that one, so probe it
                // from here and say which half is actually broken.
                fail({
                    message: `The browser could not open a console connection to ${host}.`,
                    code: closure?.code,
                })
                void reachable(session.url).then(answered =>
                    fail({
                        message: answered
                            ? `${host} answered this browser but refused the console session. The panel and the agent may no longer share a secret, or the session may have expired before it was used.`
                            : `${host} could not be reached from this browser. The agent has to be reachable from here, not only from the panel, with a certificate this browser already trusts.`,
                        code: closure?.code,
                    })
                )
            }

            if (type === 'novnc') {
                const rfb = new RFB(target.current, socket)
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
                // Unlike the socket's `open`, this fires only once RFB has
                // actually handshaken with the VNC server, so it does mean the
                // console came up.
                rfb.addEventListener('connect', () => {
                    established = true
                    setConnected(true)
                    rfb.focus()
                })
                // Guest to local. Free wherever the guest supports it, and
                // silent where it does not — the button below is the fallback
                // for browsers that refuse a write outside a user gesture.
                rfb.addEventListener('clipboard', event => {
                    const text = event.detail.text
                    if (!text) return

                    setGuestClipboard(text)
                    navigator.clipboard?.writeText(text).catch(() => {})
                })
                rfb.addEventListener('disconnect', () => {
                    setConnected(false)
                    if (!established) {
                        failToStart()

                        return
                    }

                    fail({
                        message:
                            closure?.reason ||
                            'The display console disconnected.',
                        code: closure?.code,
                        keepOutput: true,
                    })
                })
                connection.current = rfb
            } else {
                terminal = new Terminal({
                    cursorBlink: true,
                    // Without this the cursor is drawn only while focused, so
                    // a terminal you have not clicked yet looks switched off.
                    cursorInactiveStyle: 'outline',
                    convertEol: true,
                    fontFamily:
                        'ui-monospace, SFMono-Regular, Menlo, monospace',
                    theme: { background: '#09090b' },
                })
                fit = new FitAddon()
                terminal.loadAddon(fit)
                terminal.open(target.current)
                fit.fit()
                // Typing should work the moment the console opens; having to
                // click a black rectangle first reads as a dead page.
                terminal.focus()
                // NOT proof the console works: the agent upgrades the socket
                // and only then spawns the console process, so this fires even
                // when that process is about to fail. Only bytes off the wire
                // prove there is a console on the other end.
                socket.addEventListener('open', () => {
                    setConnected(true)
                    // A serial console is legitimately silent until something
                    // in the guest writes to it, so a blank screen is not
                    // evidence of a broken session — say so, rather than poke
                    // the guest with a newline it never asked for. Reconnecting
                    // mid-command, that keystroke would run whatever was typed.
                    terminal?.writeln(
                        '\x1b[2mConnected. This screen stays blank until the server writes to the serial port; press Enter to prompt it.\x1b[0m'
                    )
                })
                socket.addEventListener('message', event => {
                    established = true
                    terminal?.write(
                        event.data instanceof ArrayBuffer
                            ? new Uint8Array(event.data)
                            : event.data
                    )
                })
                socket.addEventListener('close', event => {
                    setConnected(false)
                    if (!established) {
                        failToStart()

                        return
                    }

                    // The agent puts the console process's own diagnostic in
                    // the close reason; nothing we could infer beats that. An
                    // older agent sends none, so fall back to what the close
                    // frame itself says rather than to a shrug.
                    fail({
                        message:
                            event.reason.trim() ||
                            'The console ended without saying why. Its last output is below.',
                        code: event.code,
                        keepOutput: true,
                    })
                })
                terminal.onData(
                    data =>
                        socket.readyState === WebSocket.OPEN &&
                        socket.send(data)
                )
                connection.current = socket
            }
        }
        connect().catch(error => fail({ message: reason(error) }))
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

    const display = () =>
        connection.current instanceof WebSocket ? null : connection.current

    const sendCtrlAltDel = useCallback(() => {
        if (!(connection.current instanceof WebSocket))
            connection.current?.sendCtrlAltDel()
    }, [])

    // Held down until pressed again, so the modifier composes with whatever is
    // typed on the real keyboard next. Focus goes straight back to the guest,
    // or the following keystroke would land on this button instead.
    const toggleModifier = (key: (typeof modifierKeys)[number]) => {
        const rfb = display()
        if (!rfb) return

        const down = !held.includes(key.label)
        rfb.sendKey(key.keysym, key.code, down)
        setHeld(current =>
            down
                ? [...current, key.label]
                : current.filter(label => label !== key.label)
        )
        rfb.focus()
    }

    const tapKey = (key: { keysym: number; code: string }) => {
        const rfb = display()
        if (!rfb) return

        rfb.sendKey(key.keysym, key.code)
        rfb.focus()
    }

    /**
     * The browser will only hand over the clipboard from a user gesture, and
     * Safari and Firefox may refuse outright, so a failed read is a normal
     * outcome to explain rather than an error to swallow.
     */
    const readClipboard = async () => {
        try {
            const text = await navigator.clipboard.readText()
            if (text) return text

            toast.add({ title: 'Your clipboard is empty', type: 'error' })
        } catch {
            toast.add({
                title: 'This browser would not share the clipboard',
                type: 'error',
            })
        }

        return null
    }

    // Real clipboard: instant and any size, but it reaches nothing unless the
    // guest runs an agent that implements it.
    const pasteClipboard = async () => {
        const rfb = display()
        if (!rfb) return

        const text = await readClipboard()
        if (!text) return

        rfb.clipboardPasteFrom(text)
        rfb.focus()
    }

    const typeClipboard = async () => {
        const rfb = display()
        if (!rfb || typing !== null) return

        const text = await readClipboard()
        if (!text) return

        if (text.length > typingLimit)
            toast.add({
                title: `Only the first ${typingLimit} characters will be typed`,
                type: 'error',
            })

        rfb.focus()
        setTyping(0)
        try {
            await typeText(rfb, text, done => setTyping(done))
        } finally {
            setTyping(null)
        }
    }

    // Scaling and clipping are mutually exclusive in RFB; clipping shows the
    // guest at its true resolution and scrolls, which is the only way to read a
    // low-resolution console without interpolation blur.
    useEffect(() => {
        const rfb = display()
        if (!rfb) return

        rfb.scaleViewport = fitDisplay
        rfb.clipViewport = !fitDisplay
    }, [fitDisplay, connected])

    // A modifier left down would silently poison every later keystroke, and
    // nothing on screen would explain it.
    useEffect(() => {
        if (connected) return

        setHeld([])
    }, [connected])

    const retry = (
        <Button
            variant='outline'
            className='shrink-0 border-white/15 bg-transparent text-zinc-200 hover:bg-zinc-800 hover:text-white'
            onClick={() => setAttempt(value => value + 1)}
        >
            <IconRefresh />
            Try again
        </Button>
    )

    // Proxmox refuses a display console on a VM whose display is a serial
    // terminal, and its wording ("No VNC display is present") names neither the
    // setting responsible nor what to do about it. Retrying will never help, so
    // put the fix on the failure itself rather than leaving it in the picker.
    const noDisplay =
        type === 'novnc' &&
        displayStatus !== undefined &&
        !displayStatus.enabled
    const displayFix = noDisplay && (
        <div className='flex flex-col items-center gap-3 rounded-md border border-white/10 bg-zinc-900/60 p-3'>
            <p className='text-sm text-zinc-300'>
                {displayStatus.restartRequired
                    ? `${server?.name ?? 'This server'} has a display from its next boot onwards. Restart it to use this console.`
                    : `This server's display is set to ${displayStatus.display}, so there is no screen to show. Giving it one takes a restart, and leaves the serial console as it is.`}
            </p>
            {!displayStatus.restartRequired && (
                <Button
                    variant='outline'
                    className='shrink-0 border-white/15 bg-transparent text-zinc-200 hover:bg-zinc-800 hover:text-white'
                    loading={enablingDisplay}
                    onClick={() => enableDisplay()}
                    icon={<IconDeviceDesktop />}
                >
                    Enable display output
                </Button>
            )}
        </div>
    )

    // When the console named its own failure this adds nothing, but when it
    // said nothing the close code is the only fact left — and naming it beats
    // making the user guess from a shrug.
    const closeNote = failure?.code !== undefined && (
        <p className='font-mono text-xs text-zinc-500'>
            Close code {failure.code}
            {closeCodes[failure.code] ? ` — ${closeCodes[failure.code]}` : ''}
        </p>
    )

    const tools = (
        <div className='flex items-center gap-1'>
            {type === 'novnc' && (
                <>
                    <ToolButton
                        label={
                            keysOpen
                                ? 'Hide keyboard keys'
                                : 'Keys the browser intercepts'
                        }
                        active={keysOpen || held.length > 0}
                        onClick={() => setKeysOpen(open => !open)}
                    >
                        <IconKeyboard />
                    </ToolButton>
                    <ToolButton
                        label={
                            fitDisplay ? 'Show at actual size' : 'Scale to fit'
                        }
                        onClick={() => setFitDisplay(fit => !fit)}
                    >
                        {fitDisplay ? <IconZoomIn /> : <IconArrowsMinimize />}
                    </ToolButton>
                </>
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
    )

    return (
        <div className='flex h-dvh min-h-0 flex-col bg-zinc-950 text-zinc-100'>
            {/* Chrome lives above the guest, never on top of it. Every corner of
                a desktop guest belongs to something — the tray, the Start
                button, a menu bar — so an overlaid toolbar always covers a
                control someone is reaching for, and hiding it on idle does not
                help: moving the pointer towards that corner is what brings it
                back. */}
            <header className='flex h-12 shrink-0 items-center gap-3 border-b border-white/10 px-3'>
                <Link
                    to='/servers/$serverUuid'
                    params={{ serverUuid }}
                    aria-label='Back to server'
                    className={buttonVariants({
                        variant: 'ghost',
                        size: 'icon',
                        className:
                            'text-zinc-300 hover:bg-zinc-900 hover:text-white',
                    })}
                >
                    <IconArrowLeft />
                </Link>
                <div className='min-w-0 flex-1'>
                    <p className='truncate text-sm font-medium'>
                        {server?.name ?? 'Console'}
                    </p>
                    <p className='text-xs text-zinc-400'>
                        {type === 'novnc' ? 'Display' : 'Terminal'}
                    </p>
                </div>
                {tools}
            </header>
            {type === 'novnc' && keysOpen && (
                <div className='flex shrink-0 flex-wrap items-center gap-1 border-b border-white/10 bg-zinc-900/60 px-3 py-1.5'>
                    {modifierKeys.map(key => (
                        <KeyButton
                            key={key.label}
                            held={held.includes(key.label)}
                            onClick={() => toggleModifier(key)}
                        >
                            {key.label}
                        </KeyButton>
                    ))}
                    <span className='mx-1 h-5 w-px bg-white/10' />
                    {tapKeys.map(key => (
                        <KeyButton key={key.label} onClick={() => tapKey(key)}>
                            {key.label}
                        </KeyButton>
                    ))}
                    <KeyButton onClick={sendCtrlAltDel}>Ctrl+Alt+Del</KeyButton>
                    <span className='mx-1 h-5 w-px bg-white/10' />
                    <KeyButton
                        onClick={pasteClipboard}
                        title='Send your clipboard to the guest. Needs a clipboard agent running inside it.'
                    >
                        Paste
                    </KeyButton>
                    <KeyButton
                        onClick={typeClipboard}
                        disabled={typing !== null}
                        title='Replay your clipboard as keystrokes. Works on installers and BIOS screens, but follows the guest keyboard layout.'
                    >
                        {typing === null ? 'Type' : `Typing ${typing}`}
                    </KeyButton>
                    {guestClipboard !== '' && (
                        <KeyButton
                            onClick={() => copy(guestClipboard)}
                            title='The guest copied something; put it on your clipboard.'
                        >
                            Copy from guest
                        </KeyButton>
                    )}
                </div>
            )}
            {failure?.keepOutput && (
                // In flow, not overlaid: a notice that says the output is below
                // must not be sitting on top of it.
                <div className='flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2 border-b border-white/10 bg-zinc-900/95 px-3 py-2'>
                    <div className='min-w-0 flex-1'>
                        <p className='text-sm text-zinc-200'>
                            {failure.message}
                        </p>
                        {closeNote}
                    </div>
                    {retry}
                </div>
            )}
            <main className='relative min-h-0 flex-1'>
                {!connected && !failure?.keepOutput && (
                    <div className='absolute inset-0 z-10 grid place-items-center bg-zinc-950 p-6'>
                        {failure ? (
                            <div className='flex max-w-md flex-col items-center gap-3 text-center'>
                                <p className='text-sm font-medium text-zinc-100'>
                                    {type === 'novnc'
                                        ? 'Display console unavailable'
                                        : 'Serial console unavailable'}
                                </p>
                                <p className='text-sm text-zinc-400'>
                                    {failure.message}
                                </p>
                                {closeNote}
                                {displayFix}
                                {retry}
                            </div>
                        ) : (
                            <Spinner className='size-6 text-zinc-300' />
                        )}
                    </div>
                )}
                <div
                    ref={target}
                    // `pixelated` matters when scaling up: a text console is a
                    // tiny framebuffer, and interpolating it to a 4K viewport
                    // turns every glyph to mush. Blocky beats blurry for text.
                    className='h-full w-full overflow-hidden [&_.xterm]:h-full [&_.xterm-viewport]:!overflow-y-auto [&_canvas]:mx-auto [&_canvas]:[image-rendering:pixelated]'
                />
            </main>
        </div>
    )
}

const ToolButton = ({
    label,
    onClick,
    active,
    children,
}: {
    label: string
    onClick: () => void
    active?: boolean
    children: React.ReactNode
}) => {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    size='icon'
                    variant='ghost'
                    className={cn(
                        'text-zinc-200 hover:bg-zinc-800 hover:text-white',
                        active && 'bg-zinc-800 text-white'
                    )}
                    onClick={onClick}
                >
                    {children}
                </Button>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    )
}

/**
 * A held modifier is styled as pressed, because there is nothing else on screen
 * to say the guest thinks that key is down.
 */
const KeyButton = ({
    held,
    onClick,
    disabled,
    title,
    children,
}: {
    held?: boolean
    onClick: () => void
    disabled?: boolean
    title?: string
    children: React.ReactNode
}) => {
    const button = (
        <Button
            size='sm'
            variant='ghost'
            disabled={disabled}
            className={cn(
                'h-7 min-w-9 px-2 font-mono text-xs text-zinc-200 hover:bg-zinc-800 hover:text-white',
                held &&
                    'bg-zinc-100 text-zinc-900 hover:bg-white hover:text-zinc-900'
            )}
            onClick={onClick}
        >
            {children}
        </Button>
    )

    if (!title) return button

    return (
        <Tooltip>
            <TooltipTrigger asChild>{button}</TooltipTrigger>
            <TooltipContent className='max-w-64'>{title}</TooltipContent>
        </Tooltip>
    )
}

export default ConsoleView
