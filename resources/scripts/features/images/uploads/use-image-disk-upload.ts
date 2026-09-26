import {
    UploadedDisk,
    appendImageChunk,
    cancelImageUpload,
    finalizeImageUpload,
    getImageUpload,
    openImageUpload,
} from '@/features/images/uploads/api'
import { useCallback, useRef, useState } from 'react'

export type UploadStatus =
    | 'idle'
    | 'uploading'
    | 'finalizing'
    /** The connection went, and the bytes already accepted are still there. */
    | 'interrupted'
    | 'done'
    | 'error'

interface Options {
    onComplete: (disk: UploadedDisk) => void
}

/**
 * Drives one disk image from a file input to a hashed, stored artifact.
 *
 * The server's offset is the authoritative one, so this never has to remember
 * how far it got: after a dropped connection it asks, and carries on from the
 * answer. That is why an interruption costs one chunk rather than the whole
 * transfer, and why closing the dialog does not lose the gigabytes already sent.
 */
const useImageDiskUpload = ({ onComplete }: Options) => {
    const [status, setStatus] = useState<UploadStatus>('idle')
    const [sent, setSent] = useState(0)
    const [total, setTotal] = useState(0)
    const [error, setError] = useState<string | null>(null)

    const file = useRef<File | null>(null)
    const uuid = useRef<string | null>(null)
    const aborter = useRef<AbortController | null>(null)
    /** Set only by `cancel`, so a deliberate stop is not reported as a fault. */
    const cancelled = useRef(false)

    const send = useCallback(
        async (from: number, size: number, chunkSize: number, id: string) => {
            let offset = from

            while (offset < size) {
                const chunk = file.current!.slice(
                    offset,
                    Math.min(offset + chunkSize, size)
                )
                const base = offset

                const state = await appendImageChunk(id, chunk, offset, {
                    signal: aborter.current?.signal,
                    onProgress: inChunk => setSent(base + inChunk),
                })

                offset = state.offset
                setSent(offset)
            }

            setStatus('finalizing')

            const disk = await finalizeImageUpload(id)

            uuid.current = null
            setStatus('done')
            onComplete(disk)
        },
        [onComplete]
    )

    const start = useCallback(
        async (chosen: File) => {
            file.current = chosen
            cancelled.current = false
            aborter.current = new AbortController()

            setError(null)
            setSent(0)
            setTotal(chosen.size)
            setStatus('uploading')

            try {
                const opened = await openImageUpload(chosen)
                uuid.current = opened.uuid

                await send(0, opened.size, opened.chunkSize, opened.uuid)
            } catch (e: any) {
                if (cancelled.current) return

                setError(e?.response?.data?.message ?? 'The upload stopped.')
                setStatus(uuid.current ? 'interrupted' : 'error')
            }
        },
        [send]
    )

    const resume = useCallback(async () => {
        if (!uuid.current || !file.current) return

        cancelled.current = false
        aborter.current = new AbortController()

        setError(null)
        setStatus('uploading')

        try {
            // Asked rather than assumed: a chunk that arrived incomplete was
            // rolled back, so the client's idea of where it got to can be ahead
            // of the file that actually exists.
            const state = await getImageUpload(uuid.current)

            setSent(state.offset)

            await send(state.offset, state.size, state.chunkSize, state.uuid)
        } catch (e: any) {
            if (cancelled.current) return

            setError(e?.response?.data?.message ?? 'The upload stopped.')
            setStatus('interrupted')
        }
    }, [send])

    const cancel = useCallback(async () => {
        cancelled.current = true
        aborter.current?.abort()

        const id = uuid.current
        uuid.current = null
        file.current = null

        setStatus('idle')
        setSent(0)
        setTotal(0)
        setError(null)

        if (id) {
            // Best effort: the sweep reclaims anything this misses.
            await cancelImageUpload(id).catch(() => undefined)
        }
    }, [])

    return {
        status,
        sent,
        total,
        error,
        percent: total > 0 ? Math.min(100, (sent / total) * 100) : 0,
        isBusy: status === 'uploading' || status === 'finalizing',
        start,
        resume,
        cancel,
    }
}

export default useImageDiskUpload
