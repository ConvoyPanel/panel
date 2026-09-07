import { AvatarCrop } from '@/features/account/profile/api.ts'
import { cn } from '@/utils'
import { useEffect, useRef, useState } from 'react'

import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'

/** Edge of the framing viewport, in CSS pixels. */
const VIEWPORT = 288

const MAX_ZOOM = 4

interface Props {
    /** The picture being framed; null closes the dialog. */
    file: File | null
    onCancel: () => void
    onConfirm: (file: File, crop: AvatarCrop) => void
    busy?: boolean
}

interface Offset {
    x: number
    y: number
}

/**
 * Frames an uploaded picture before it is sent.
 *
 * The dialog never touches pixels: it works out which square of the *original*
 * the user framed and hands those coordinates to the panel, which cuts and
 * re-encodes once. Cropping here instead would mean encoding the picture in the
 * browser and again on the server, for a worse result.
 */
const AvatarCropDialog = ({ file, onCancel, onConfirm, busy }: Props) => {
    const [url, setUrl] = useState<string | null>(null)
    const [natural, setNatural] = useState<{ w: number; h: number } | null>(
        null
    )
    const [zoom, setZoom] = useState(1)
    const [offset, setOffset] = useState<Offset>({ x: 0, y: 0 })
    const drag = useRef<{ x: number; y: number; from: Offset } | null>(null)

    // Revoked on the way out: an object URL holds the whole file in memory
    // until it is, and picking three pictures in a row would keep all three.
    useEffect(() => {
        if (!file) return

        const next = URL.createObjectURL(file)

        setUrl(next)
        setNatural(null)
        setZoom(1)
        setOffset({ x: 0, y: 0 })

        return () => URL.revokeObjectURL(next)
    }, [file])

    // The scale at which the picture's shorter side exactly fills the viewport,
    // which is the smallest it may be drawn without a gap at an edge.
    const cover = natural ? VIEWPORT / Math.min(natural.w, natural.h) : 1
    const scale = cover * zoom
    const drawn = natural
        ? { w: natural.w * scale, h: natural.h * scale }
        : { w: 0, h: 0 }

    /** Keeps the picture covering the viewport, whatever the pan and zoom. */
    const contain = (next: Offset, size = drawn): Offset => ({
        x: Math.min(0, Math.max(VIEWPORT - size.w, next.x)),
        y: Math.min(0, Math.max(VIEWPORT - size.h, next.y)),
    })

    const onLoad = (event: React.SyntheticEvent<HTMLImageElement>) => {
        const { naturalWidth: w, naturalHeight: h } = event.currentTarget
        const fit = VIEWPORT / Math.min(w, h)

        setNatural({ w, h })
        // Opens centred, which is the crop the panel would have taken anyway —
        // so confirming without touching anything changes nothing.
        setOffset({
            x: (VIEWPORT - w * fit) / 2,
            y: (VIEWPORT - h * fit) / 2,
        })
    }

    const changeZoom = (next: number) => {
        if (!natural) return

        const clamped = Math.min(MAX_ZOOM, Math.max(1, next))
        const size = {
            w: natural.w * cover * clamped,
            h: natural.h * cover * clamped,
        }

        // Zoom about the middle of the frame rather than the top-left, or the
        // subject slides out of shot as it grows.
        const ratio = clamped / zoom

        setOffset(
            contain(
                {
                    x: VIEWPORT / 2 - (VIEWPORT / 2 - offset.x) * ratio,
                    y: VIEWPORT / 2 - (VIEWPORT / 2 - offset.y) * ratio,
                },
                size
            )
        )
        setZoom(clamped)
    }

    const confirm = () => {
        if (!file || !natural) return

        onConfirm(file, {
            x: Math.round(-offset.x / scale),
            y: Math.round(-offset.y / scale),
            size: Math.round(VIEWPORT / scale),
        })
    }

    return (
        <ResponsiveDialog
            open={file !== null}
            onOpenChange={open => !open && onCancel()}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Crop Your Picture
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody
                    className={'flex flex-col items-center gap-4'}
                >
                    <div
                        className={cn(
                            'bg-muted relative touch-none overflow-hidden rounded-full select-none',
                            drag.current ? 'cursor-grabbing' : 'cursor-grab'
                        )}
                        style={{ width: VIEWPORT, height: VIEWPORT }}
                        onPointerDown={event => {
                            event.currentTarget.setPointerCapture(
                                event.pointerId
                            )
                            drag.current = {
                                x: event.clientX,
                                y: event.clientY,
                                from: offset,
                            }
                        }}
                        onPointerMove={event => {
                            const start = drag.current

                            if (!start) return

                            setOffset(
                                contain({
                                    x: start.from.x + (event.clientX - start.x),
                                    y: start.from.y + (event.clientY - start.y),
                                })
                            )
                        }}
                        onPointerUp={() => {
                            drag.current = null
                        }}
                        onWheel={event =>
                            changeZoom(zoom - event.deltaY * 0.002)
                        }
                    >
                        {url && (
                            <img
                                src={url}
                                alt={''}
                                onLoad={onLoad}
                                draggable={false}
                                className={'max-w-none origin-top-left'}
                                style={{
                                    width: drawn.w || undefined,
                                    height: drawn.h || undefined,
                                    transform: `translate(${offset.x}px, ${offset.y}px)`,
                                }}
                            />
                        )}
                    </div>

                    <label
                        className={'flex w-full items-center gap-3'}
                        style={{ maxWidth: VIEWPORT }}
                    >
                        <span className={'text-muted-foreground text-sm'}>
                            Zoom
                        </span>
                        <input
                            type={'range'}
                            min={1}
                            max={MAX_ZOOM}
                            step={0.01}
                            value={zoom}
                            disabled={!natural}
                            onChange={event =>
                                changeZoom(Number(event.target.value))
                            }
                            className={'accent-primary h-1 flex-1'}
                        />
                    </label>
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter className={'mt-4'}>
                    <Button
                        variant={'outline'}
                        type={'button'}
                        onClick={onCancel}
                    >
                        Cancel
                    </Button>
                    <Button
                        loading={busy}
                        disabled={!natural || busy}
                        onClick={confirm}
                    >
                        Save picture
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AvatarCropDialog
