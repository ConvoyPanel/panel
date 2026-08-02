import {
    type ConsoleType,
    type SerialConsole,
    enableSerialConsole,
    useSerialConsole,
} from '@/features/servers/console/api'
import useConsoleType from '@/features/servers/console/use-console-type.ts'
import { useServer } from '@/features/servers/detail/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import type { Server } from '@/types/server'
import {
    IconChevronDown,
    IconDeviceDesktop,
    IconExternalLink,
    IconSettings,
    IconTerminal2,
} from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { Link, useNavigate } from '@tanstack/react-router'
import { useState } from 'react'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { ButtonGroup, ButtonGroupSeparator } from '@/components/ui/ButtonGroup'
import { Checkbox } from '@/components/ui/Checkbox'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import {
    Item,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'
import { Label } from '@/components/ui/Label'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

const consoles = [
    {
        type: 'novnc',
        label: 'Display',
        icon: IconDeviceDesktop,
        description:
            'Graphical output with mouse and keyboard. Use this for installers and boot screens.',
    },
    {
        type: 'xtermjs',
        label: 'Serial terminal',
        icon: IconTerminal2,
        description:
            'Text only, with copy and paste. Holds up over a slow connection.',
    },
] satisfies {
    type: ConsoleType
    label: string
    icon: typeof IconTerminal2
    description: string
}[]

// Shared by the live control and its disabled stand-in, so nothing shifts.
// The group owns the joined corners; these only carry width.
const shell = 'w-full @sm:w-fit'
const leadingHalf = 'flex-1 @sm:flex-none'
const trailingHalf = 'w-7 px-0'

const ConsoleButton = () => {
    const { data: server } = useServer()

    if (!server)
        return (
            <ButtonGroup className={shell}>
                <Button className={leadingHalf} disabled>
                    <IconTerminal2 />
                    Console
                </Button>
                <ButtonGroupSeparator />
                <Button className={trailingHalf} disabled aria-hidden>
                    <IconChevronDown />
                </Button>
            </ButtonGroup>
        )

    return <ConsoleControls server={server} />
}

const ConsoleControls = ({ server }: { server: Server }) => {
    const [lastUsed, rememberConsoleType] = useConsoleType()
    const [picking, setPicking] = useState(false)
    const [newWindow, setNewWindow] = useState(false)
    const navigate = useNavigate()

    const consoleLink = (type: ConsoleType) => ({
        to: '/servers/$serverUuid/console' as const,
        params: { serverUuid: server.uuid },
        search: { type },
    })

    // Every route into the console records the type on the way through, which
    // is what lets the main half of the split button skip the picker later.
    const launched = (type: ConsoleType) => () => {
        rememberConsoleType(type)
        setPicking(false)
    }

    const open = (type: ConsoleType) => {
        launched(type)()
        navigate(consoleLink(type))
    }

    return (
        <>
            <ButtonGroup className={shell}>
                <Button
                    className={leadingHalf}
                    // Nothing remembered means nothing to open confidently, so
                    // the picker explains the two before committing to one.
                    onClick={() =>
                        lastUsed ? open(lastUsed) : setPicking(true)
                    }
                >
                    <IconTerminal2 />
                    Console
                </Button>
                <ButtonGroupSeparator />
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            aria-label={'Console options'}
                            className={trailingHalf}
                        >
                            <IconChevronDown />
                        </Button>
                    </DropdownMenuTrigger>
                    {/* The menu takes the trigger's width by default, and the
                        trigger here is a 28px chevron. */}
                    <DropdownMenuContent align={'end'} className={'w-56'}>
                        {consoles.map(({ type, label, icon: Icon }) => (
                            <DropdownMenuItem key={type} asChild>
                                <Link
                                    {...consoleLink(type)}
                                    onClick={launched(type)}
                                >
                                    <Icon />
                                    Open {label.toLowerCase()}
                                </Link>
                            </DropdownMenuItem>
                        ))}
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link
                                {...consoleLink(lastUsed ?? 'novnc')}
                                target={'_blank'}
                                rel={'noopener noreferrer'}
                                onClick={launched(lastUsed ?? 'novnc')}
                            >
                                <IconExternalLink />
                                Open in new window
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => setPicking(true)}>
                            <IconSettings />
                            Console options…
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </ButtonGroup>
            <ResponsiveDialog open={picking} onOpenChange={setPicking}>
                <ResponsiveDialogContent>
                    <ResponsiveDialogHeader>
                        <ResponsiveDialogTitle>
                            Open console
                        </ResponsiveDialogTitle>
                        <ResponsiveDialogDescription>
                            Choose how you want to connect to {server.name}.
                        </ResponsiveDialogDescription>
                    </ResponsiveDialogHeader>
                    <ResponsiveDialogBody className={'flex flex-col gap-2'}>
                        {consoles.map(
                            ({ type, label, description, icon: Icon }) => (
                                <div
                                    key={type}
                                    className={'flex flex-col gap-2'}
                                >
                                    <Item
                                        asChild
                                        variant={'outline'}
                                        size={'sm'}
                                        // A row here is one of two choices, not
                                        // an entry in a list.
                                        role={undefined}
                                    >
                                        <Link
                                            {...consoleLink(type)}
                                            target={
                                                newWindow ? '_blank' : undefined
                                            }
                                            rel={
                                                newWindow
                                                    ? 'noopener noreferrer'
                                                    : undefined
                                            }
                                            onClick={launched(type)}
                                            className={'hover:border-ring'}
                                        >
                                            <ItemMedia variant={'icon'}>
                                                <Icon />
                                            </ItemMedia>
                                            <ItemContent>
                                                <ItemTitle>
                                                    {label}
                                                    {lastUsed === type && (
                                                        <Badge
                                                            variant={
                                                                'secondary'
                                                            }
                                                            className={
                                                                'font-medium'
                                                            }
                                                        >
                                                            Last used
                                                        </Badge>
                                                    )}
                                                </ItemTitle>
                                                <ItemDescription>
                                                    {description}
                                                </ItemDescription>
                                            </ItemContent>
                                        </Link>
                                    </Item>
                                    {type === 'xtermjs' && (
                                        <SerialDeviceNotice
                                            server={server}
                                            open={picking}
                                        />
                                    )}
                                </div>
                            )
                        )}
                        <Label
                            className={'text-muted-foreground mt-1 font-normal'}
                        >
                            <Checkbox
                                checked={newWindow}
                                onCheckedChange={checked =>
                                    setNewWindow(checked)
                                }
                            />
                            Open in a new window
                        </Label>
                    </ResponsiveDialogBody>
                </ResponsiveDialogContent>
            </ResponsiveDialog>
        </>
    )
}

/**
 * The serial console attaches to a serial device on the VM, and a server built
 * before those were provisioned has none — the terminal then closes without
 * ever carrying a frame. Rather than hiding or disabling the option, say what
 * is missing and offer to add it; PVE cannot hot-add the device, so the reward
 * for enabling it is a restart the user chooses to make.
 */
const SerialDeviceNotice = ({
    server,
    open,
}: {
    server: Server
    open: boolean
}) => {
    // Reaches the node, so it is only asked for once the dialog is actually up.
    const { data: serial, isPending } = useSerialConsole(server.uuid, open)
    const refresh = useQueryMutator<SerialConsole>([
        'servers',
        server.uuid,
        'serial-console',
    ])

    const { mutate, isPending: enabling } = useMutation({
        mutationFn: () => enableSerialConsole(server.uuid),
        onSuccess: status => refresh(() => status),
        onError: () =>
            toast.add({
                title: 'Could not add the serial device',
                type: 'error',
            }),
    })

    if (isPending || !serial || serial.enabled) return null

    return (
        <div
            className={
                'text-muted-foreground border-border ml-2 flex flex-col gap-2 border-l pl-3 text-xs'
            }
        >
            {serial.restartRequired ? (
                <p>
                    The serial device is added but only appears once the server
                    boots. Restart {server.name} to finish enabling it.
                </p>
            ) : (
                <>
                    <p>
                        This server has no serial device, so the terminal will
                        connect to nothing. Adding one takes a restart.
                    </p>
                    <Button
                        variant={'outline'}
                        size={'sm'}
                        className={'w-fit'}
                        loading={enabling}
                        onClick={() => mutate()}
                    >
                        Add serial device
                    </Button>
                </>
            )}
        </div>
    )
}

export default ConsoleButton
