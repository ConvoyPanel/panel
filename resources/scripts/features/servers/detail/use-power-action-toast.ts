import { useServerState } from '@/features/servers/detail/api.ts'
import type { PowerActionResult } from '@/types/server.ts'
import { useParams } from '@tanstack/react-router'
import { useEffect, useRef } from 'react'

import { toast } from '@/components/ui/Toast'

type Command = PowerActionResult['command']

const progressLabel: Record<Command, string> = {
    start: 'Starting server…',
    restart: 'Restarting server…',
    reset: 'Resetting server…',
    resume: 'Resuming server…',
    suspend: 'Suspending server…',
    shutdown: 'Shutting down server…',
    kill: 'Killing server…',
}

const successLabel: Record<Command, string> = {
    start: 'Server started',
    restart: 'Server restarted',
    reset: 'Server reset',
    resume: 'Server resumed',
    suspend: 'Server suspended',
    shutdown: 'Server shut down',
    kill: 'Server killed',
}

const failureLabel: Record<Command, string> = {
    start: 'Failed to start server',
    restart: 'Failed to restart server',
    reset: 'Failed to reset server',
    resume: 'Failed to resume server',
    suspend: 'Failed to suspend server',
    shutdown: 'Failed to shut down server',
    kill: 'Failed to kill server',
}

// Proxmox error strings can be long; keep the toast description readable rather
// than letting a wall of text stretch it.
const MAX_DETAIL = 200

/**
 * Drives a single loading toast off the polled power-action state, so it works
 * the same whether the action was just started here or was already in flight
 * when the page loaded (a reload re-raises it from `pendingPowerAction`).
 *
 * The toast resolves to success/failure from `lastPowerAction` — but only for
 * the action it was actually showing, so a stale result from an earlier visit
 * can't pop a toast the moment you open a server. It is dismissed on unmount so
 * an in-progress action doesn't trail a spinner across the rest of the app when
 * you navigate away after a quick look.
 *
 * Mount once per server view (e.g. the overview toolbar).
 */
export const usePowerActionToast = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid?: string }
    const serverUuid = uuid ?? params.serverUuid

    const { data } = useServerState(serverUuid)
    const pending = data?.pendingPowerAction ?? null
    const result = data?.lastPowerAction ?? null

    // requestedAt of the action currently shown as in progress, or null.
    const activeRef = useRef<string | null>(null)
    const toastId = `power:${serverUuid ?? 'unknown'}`

    useEffect(() => {
        if (pending) {
            if (activeRef.current !== pending.requestedAt) {
                activeRef.current = pending.requestedAt
                // Adding under an existing id merges into that toast rather
                // than replacing it, so the fields a previous cycle's failure
                // set are cleared here instead of bleeding into this one.
                toast.add({
                    id: toastId,
                    title: progressLabel[pending.command],
                    description: undefined,
                    timeout: undefined,
                    type: 'loading',
                })
            }
            return
        }

        if (!activeRef.current) return

        // The action we were showing has cleared. Resolve it from the matching
        // result; if none is present, just drop the spinner.
        const finished = activeRef.current
        activeRef.current = null

        if (result && result.requestedAt === finished) {
            if (result.ok) {
                toast.add({
                    id: toastId,
                    title: successLabel[result.command],
                    type: 'success',
                })
            } else {
                toast.add({
                    id: toastId,
                    title: failureLabel[result.command],
                    description: result.exitStatus?.slice(0, MAX_DETAIL),
                    timeout: 8000,
                    type: 'error',
                })
            }
        } else {
            toast.close(toastId)
        }
    }, [pending, result, toastId])

    useEffect(() => {
        return () => {
            toast.close(toastId)
            activeRef.current = null
        }
    }, [toastId])
}
