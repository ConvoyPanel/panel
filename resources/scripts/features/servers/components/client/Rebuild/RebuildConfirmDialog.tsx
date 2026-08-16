import { IconCheck, IconTrash } from '@tabler/icons-react'
import byteSize from 'byte-size'
import { type ReactNode, useEffect, useState } from 'react'

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

interface Props {
    open: boolean
    onOpenChange: (open: boolean) => void
    /** Typed back by the user to confirm; also the dialog's subject. */
    serverName: string
    /** Bytes, straight off the server record. */
    disk: number
    /** "Ubuntu 24.04 LTS" — the group and template the form settled on. */
    templateLabel: string
    onConfirm: () => void
    isPending: boolean
    /**
     * The identity gate, when one is up. It renders *inside* this popup on
     * purpose: Base UI then treats it as a nested dialog, so it gets no
     * backdrop of its own and this dialog stays visible behind it instead of
     * the two swapping with a backdrop flash. See AuthDialog's own docblock.
     */
    children?: ReactNode
}

const RebuildConfirmDialog = ({
    open,
    onOpenChange,
    serverName,
    disk,
    templateLabel,
    onConfirm,
    isPending,
    children,
}: Props) => {
    const [typedName, setTypedName] = useState('')

    // Reopening must not inherit the last attempt's confirmation.
    useEffect(() => {
        if (open) setTypedName('')
    }, [open])

    return (
        <ResponsiveDialog open={open} onOpenChange={onOpenChange}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Rebuild {serverName}?
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        This cannot be undone. Type the server's name to
                        confirm.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>

                <ResponsiveDialogBody className={'flex flex-col gap-4'}>
                    <ul
                        className={
                            'bg-muted flex flex-col gap-2 rounded-lg p-3 text-sm'
                        }
                    >
                        <li className={'flex items-start gap-2'}>
                            <IconTrash
                                className={'text-destructive mt-0.5 size-4'}
                            />
                            <span>
                                {byteSize(disk, { units: 'iec' }).toString()}{' '}
                                disk erased
                            </span>
                        </li>
                        <li className={'flex items-start gap-2'}>
                            <IconCheck className={'mt-0.5 size-4'} />
                            <span>{templateLabel} installed</span>
                        </li>
                        <li className={'flex items-start gap-2'}>
                            <IconCheck className={'mt-0.5 size-4'} />
                            <span>
                                Backups, IP addresses and firewall rules kept
                            </span>
                        </li>
                    </ul>

                    <div className={'flex flex-col gap-2'}>
                        <Label htmlFor={'confirm-server-name'}>
                            Server name
                        </Label>
                        <Input
                            id={'confirm-server-name'}
                            value={typedName}
                            placeholder={serverName}
                            autoComplete={'off'}
                            onChange={e => setTypedName(e.target.value)}
                        />
                    </div>
                </ResponsiveDialogBody>

                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={
                            <Button
                                variant={'outline'}
                                type={'button'}
                                disabled={isPending}
                            >
                                Cancel
                            </Button>
                        }
                    />
                    <Button
                        type={'button'}
                        variant={'destructive'}
                        loading={isPending}
                        disabled={typedName.trim() !== serverName}
                        onClick={onConfirm}
                    >
                        Rebuild server
                    </Button>
                </ResponsiveDialogFooter>

                {children}
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default RebuildConfirmDialog
