import type { StorageConsumer } from '@/features/nodes/storages/api.ts'
import byteSize from 'byte-size'
import { useEffect, useState } from 'react'

import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Label } from '@/components/ui/Label'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'

const fmt = (bytes: number) => {
    const { value, unit } = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${value} ${unit}`
}

interface Props {
    server: StorageConsumer | null
    onOpenChange: (open: boolean) => void
    onConfirm: () => void
    isPending: boolean
}

/**
 * Deleting a server from the storage screen.
 *
 * The name has to be typed. A backup is a copy and an ISO is a download, so
 * getting either wrong costs time; a server is somebody's machine and getting it
 * wrong costs their data. It is the one row on this page where the action is not
 * recoverable, so it is the one that asks for more than a click.
 *
 * Modelled on RebuildConfirmDialog, which already sets this bar for the same
 * reason on the client side.
 */
const DeleteServerDialog = ({
    server,
    onOpenChange,
    onConfirm,
    isPending,
}: Props) => {
    const [typedName, setTypedName] = useState('')

    // Reopening must not inherit the last attempt's confirmation.
    useEffect(() => {
        if (server) setTypedName('')
    }, [server])

    const matches = typedName.trim() === server?.name

    return (
        <ResponsiveDialog
            open={server !== null}
            onOpenChange={open => !open && onOpenChange(false)}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Delete {server?.name}?
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        This destroys the server and everything on it. It cannot
                        be undone.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>

                <ResponsiveDialogBody className={'flex flex-col gap-4'}>
                    <dl className={'grid grid-cols-2 gap-y-2 text-sm'}>
                        <dt className={'text-muted-foreground'}>Owner</dt>
                        <dd className={'text-right'}>{server?.owner ?? '—'}</dd>
                        <dt className={'text-muted-foreground'}>
                            Reclaimed here
                        </dt>
                        <dd className={'text-right font-mono tabular-nums'}>
                            {server ? fmt(server.size) : '—'}
                        </dd>
                    </dl>

                    <div className={'flex flex-col gap-2'}>
                        <Label htmlFor={'confirm-server-name'}>
                            Type <b>{server?.name}</b> to confirm
                        </Label>
                        <Input
                            id={'confirm-server-name'}
                            autoComplete={'off'}
                            value={typedName}
                            onChange={event => setTypedName(event.target.value)}
                        />
                    </div>
                </ResponsiveDialogBody>

                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={<Button variant={'outline'}>Cancel</Button>}
                    />
                    <Button
                        variant={'destructive'}
                        disabled={!matches}
                        loading={isPending}
                        onClick={onConfirm}
                    >
                        Delete server
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default DeleteServerDialog
