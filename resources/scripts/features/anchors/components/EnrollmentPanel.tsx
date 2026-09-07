import type { AnchorEnrollment } from '@/features/anchors/types.ts'
import useClipboard from '@/hooks/use-clipboard.ts'
import { getApiErrorMessage } from '@/utils/http.ts'
import { IconCopy } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { useEffect, useRef, useState } from 'react'

import { Button } from '@/components/ui/Button'
import { CardContent } from '@/components/ui/Card'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    /** Shown once it arrives, when the build is known. */
    version?: string | null
    /**
     * Mints the token. Passed in rather than chosen here, because the same
     * panel serves three jobs that differ only in which endpoint issues:
     * enrolling a brand-new machine, installing an agent on a node carried over
     * from v4, and re-keying a relay.
     */
    issueToken: () => Promise<AnchorEnrollment>
    /** Something has arrived, so stop polling and stop offering the command. */
    done: boolean
    /** A stable id for what this panel is issuing for; re-issues when it changes. */
    subject: number | string
    /** Invalidates whatever list is watching for the arrival. */
    refresh: () => Promise<unknown>
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
 * its own from the row menu, so it owns the body rather than a dialog of its
 * own.
 *
 * There is deliberately no "waiting for…" line and no status dot. Nothing has
 * happened yet, the surrounding page already said what is being waited for, and
 * the arrival announces itself as its own card -- a sentence that never changes
 * is not a status, and a dot only earns its keep in a list where a column of
 * them can be scanned.
 */
const EnrollmentPanel = ({
    version,
    issueToken,
    done,
    subject,
    refresh,
}: Props) => {
    const { copy } = useClipboard({ successMessage: 'Install command copied' })
    const enrolled = done
    const issue = useMutation({ mutationFn: issueToken })
    const remaining = useCountdown(issue.data?.expiresAt)

    // Issuing a token is a write, so it happens once per open rather than on
    // every render StrictMode's double-mount included.
    const issuedFor = useRef<number | string | null>(null)
    useEffect(() => {
        if (issuedFor.current === subject) return

        issuedFor.current = subject
        issue.mutate()
    }, [subject, issue])

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
        <CardContent className={'flex flex-col gap-2.5'}>
            {issue.isPending && <Skeleton className={'h-12 w-full'} />}

            {issue.isError && (
                <div className={'flex items-center justify-between gap-3'}>
                    <p className={'text-destructive text-sm'}>
                        {getApiErrorMessage(
                            issue.error,
                            'Could not create an install command.'
                        )}
                    </p>
                    <Button
                        variant={'outline'}
                        size={'sm'}
                        onClick={() => issue.mutate()}
                    >
                        Try again
                    </Button>
                </div>
            )}

            {issue.data && (
                <>
                    {/* An inset field rather than a filled slab. This is one
                        line pasted once, and a black block was the loudest
                        object on the screen -- it is still not a text input,
                        so it stays a `code` with no affordance to type into. */}
                    <div
                        className={
                            'bg-muted/70 flex items-start gap-2 rounded-lg border p-2.5 pl-3'
                        }
                    >
                        <code
                            className={
                                'min-w-0 flex-1 py-0.5 font-mono text-xs leading-relaxed break-all'
                            }
                        >
                            <span
                                className={'text-muted-foreground select-none'}
                            >
                                ${' '}
                            </span>
                            {issue.data.command}
                        </code>
                        <Button
                            variant={'ghost'}
                            size={'sm'}
                            aria-label={'Copy install command'}
                            className={'text-muted-foreground shrink-0'}
                            onClick={() => copy(issue.data.command)}
                        >
                            <IconCopy className={'size-3.5'} />
                            Copy
                        </Button>
                    </div>

                    {/*
                     * One line under the field, hung off its trailing edge so
                     * it reads against the Copy button rather than opening a
                     * second left-hand column. Whatever is true right now sits
                     * on the right; anything that needs saying alongside it is
                     * pushed to the left with `mr-auto`.
                     */}
                    <div
                        className={
                            'text-muted-foreground flex flex-wrap items-center justify-end gap-x-3 gap-y-1 text-xs'
                        }
                    >
                        {/* Enrolling rotates the secret, which is the point --
                            it is how a leaked anchor.toml is revoked -- but it
                            means running this against a working anchor cuts it
                            off until it finishes. */}
                        {enrolled && (
                            <span className={'mr-auto'}>
                                Running this issues a new secret; the current
                                install stops working until it re-enrolls.
                            </span>
                        )}

                        {enrolled ? (
                            <span>
                                <span className={'text-success'}>Enrolled</span>
                                {/* The version answers a different question
                                    than "did it work", so it trails the state
                                    as an aside rather than joining it. */}
                                {version && <> · </>}
                                {version && (
                                    <span className={'tabular-nums'}>
                                        {version}
                                    </span>
                                )}
                            </span>
                        ) : (
                            remaining !== null &&
                            (remaining > 0 ? (
                                <span className={'tabular-nums'}>
                                    expires in {format(remaining)}
                                </span>
                            ) : (
                                <span className={'text-destructive'}>
                                    This command has expired. Reissue it to
                                    finish setup.
                                </span>
                            ))
                        )}
                    </div>
                </>
            )}
        </CardContent>
    )
}

export default EnrollmentPanel
