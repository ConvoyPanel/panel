import { createEnrollment } from '@/features/anchors/api.ts'
import { toneDotClass } from '@/features/anchors/status.ts'
import type { Anchor } from '@/features/anchors/types.ts'
import useClipboard from '@/hooks/use-clipboard.ts'
import { getApiErrorMessage } from '@/utils/http.ts'
import { IconCheck, IconCopy } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { useEffect, useRef, useState } from 'react'

import { Button } from '@/components/ui/Button'
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/InputGroup'
import {
    ResponsiveDialogBody,
    ResponsiveDialogFooter,
} from '@/components/ui/ResponsiveDialog'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    anchor: Anchor
    /** Invalidates the anchor list; used to watch for the first heartbeat. */
    refresh: () => Promise<unknown>
    onClose: () => void
}

const format = (seconds: number) =>
    `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`

/**
 * Seconds left on the outstanding token. The command stops working when this
 * hits zero, and a command that quietly expires is indistinguishable from one
 * that never worked.
 */
const useCountdown = (expiresAt?: string) => {
    const [remaining, setRemaining] = useState<number | null>(null)

    useEffect(() => {
        if (!expiresAt) {
            setRemaining(null)

            return
        }

        const tick = () =>
            setRemaining(
                Math.max(
                    0,
                    Math.floor(
                        (new Date(expiresAt).getTime() - Date.now()) / 1000
                    )
                )
            )

        tick()
        const interval = setInterval(tick, 1000)

        return () => clearInterval(interval)
    }, [expiresAt])

    return remaining
}

/**
 * The second half of adding an anchor: the command to run on the box, and the
 * wait for it to call home. Rendered both as step 2 of the create dialog and on
 * its own from the row menu, so it owns the body and footer rather than a
 * dialog of its own.
 */
const EnrollmentPanel = ({ anchor, refresh, onClose }: Props) => {
    const { copy } = useClipboard({ successMessage: 'Install command copied' })
    const enrolled = anchor.compatibility !== 'unenrolled'
    const issue = useMutation({ mutationFn: () => createEnrollment(anchor.id) })
    const remaining = useCountdown(issue.data?.expiresAt)

    // Issuing a token is a write, so it happens once per open rather than on
    // every render StrictMode's double-mount included.
    const issuedFor = useRef<number | null>(null)
    useEffect(() => {
        if (issuedFor.current === anchor.id) return

        issuedFor.current = anchor.id
        issue.mutate()
    }, [anchor.id, issue])

    /*
     * The list already polls every 30s, which is fine for a screen you glance
     * at and far too slow for a screen you are watching. While this is open and
     * the anchor still hasn't enrolled, poll it hard: the flip to "Enrolled" is
     * the entire payoff of the flow.
     *
     * `refresh` is read through a ref so a caller that rebuilds the callback
     * each render doesn't restart the interval every render.
     */
    const refreshRef = useRef(refresh)
    refreshRef.current = refresh
    useEffect(() => {
        if (enrolled) return

        const interval = setInterval(() => void refreshRef.current(), 5_000)

        return () => clearInterval(interval)
    }, [enrolled])

    return (
        <>
            <ResponsiveDialogBody className='flex flex-col gap-4'>
                {issue.isPending && <Skeleton className='h-9 w-full' />}

                {issue.isError && (
                    <div className='flex items-center justify-between gap-3'>
                        <p className='text-destructive text-sm'>
                            {getApiErrorMessage(
                                issue.error,
                                'Could not create an install command.'
                            )}
                        </p>
                        <Button
                            variant='outline'
                            size='sm'
                            onClick={() => issue.mutate()}
                        >
                            Try again
                        </Button>
                    </div>
                )}

                {issue.data && (
                    <>
                        <InputGroup>
                            <InputGroupInput
                                readOnly
                                value={issue.data.command}
                                className='font-mono text-xs'
                                aria-label='Install command'
                                onFocus={event => event.currentTarget.select()}
                            />
                            <InputGroupAddon align='inline-end'>
                                <InputGroupButton
                                    onClick={() => copy(issue.data.command)}
                                >
                                    <IconCopy className='size-4' />
                                    Copy
                                </InputGroupButton>
                            </InputGroupAddon>
                        </InputGroup>

                        {!enrolled && remaining !== null && (
                            <p className='text-muted-foreground text-xs tabular-nums'>
                                {remaining > 0 ? (
                                    <>Expires in {format(remaining)}</>
                                ) : (
                                    <>
                                        This command has expired. Reissue it to
                                        finish setup.
                                    </>
                                )}
                            </p>
                        )}

                        {/* Enrolling rotates the secret, which is the point --
                            it is how a leaked anchor.toml is revoked -- but it
                            means running this against a working anchor cuts it
                            off until it finishes. */}
                        {enrolled && (
                            <p className='text-muted-foreground text-xs'>
                                Running this issues a new secret; the current
                                install stops working until it re-enrolls.
                            </p>
                        )}
                    </>
                )}

                <div className='bg-muted/50 flex items-center gap-2.5 rounded-lg border p-3 text-sm'>
                    {enrolled ? (
                        <>
                            <IconCheck
                                className='text-success size-4 shrink-0'
                                aria-hidden
                            />
                            <span>Enrolled</span>
                            {/* The version is the answer to a different
                                question than "did it work", so it sits at the
                                far edge rather than trailing the sentence. */}
                            {anchor.version && (
                                <span className='text-muted-foreground ml-auto font-mono text-xs tabular-nums'>
                                    {anchor.version}
                                </span>
                            )}
                        </>
                    ) : (
                        <>
                            <span
                                className={`size-2 shrink-0 rounded-full ${toneDotClass.waiting}`}
                                aria-hidden
                            />
                            <span>
                                Waiting for{' '}
                                <span className='font-medium'>
                                    {anchor.name}
                                </span>{' '}
                                to call home…
                            </span>
                        </>
                    )}
                </div>

                {!enrolled && (
                    <p className='text-muted-foreground text-xs'>
                        You can close this — the anchor stays{' '}
                        <span className='font-medium'>Waiting for install</span>{' '}
                        in the list until it connects, and the command can be
                        reissued from its menu.
                    </p>
                )}
            </ResponsiveDialogBody>
            <ResponsiveDialogFooter className='mt-4'>
                <Button
                    variant={enrolled ? 'default' : 'outline'}
                    onClick={onClose}
                >
                    {enrolled ? 'Done' : 'Close'}
                </Button>
            </ResponsiveDialogFooter>
        </>
    )
}

export default EnrollmentPanel
