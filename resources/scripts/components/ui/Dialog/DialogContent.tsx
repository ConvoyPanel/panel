import { cn } from '@/utils'
import { Dialog as DialogPrimitive } from '@base-ui/react/dialog'
import { useMergedRefs } from '@base-ui/utils/useMergedRefs'
import { IconX } from '@tabler/icons-react'
import {
    CSSProperties,
    ComponentProps,
    createContext,
    useCallback,
    useContext,
    useId,
    useLayoutEffect,
    useMemo,
    useState,
} from 'react'

import { DialogOverlay, DialogPortal } from '@/components/ui/Dialog'

type DialogPopupProps = ComponentProps<typeof DialogPrimitive.Popup>
type ReportDialogHeight = (id: string, height: number | null) => void

const DialogStackContext = createContext<ReportDialogHeight | null>(null)

type DialogStackStyle = CSSProperties & {
    '--dialog-height': string
    '--frontmost-dialog-height': string
}

const DialogContent = ({
    className,
    children,
    ref,
    style,
    ...props
}: DialogPopupProps) => {
    const parentReporter = useContext(DialogStackContext)
    const dialogId = useId()
    const [popup, setPopup] = useState<HTMLDivElement | null>(null)
    const [ownHeight, setOwnHeight] = useState(0)
    const [frontmostDescendantHeight, setFrontmostDescendantHeight] = useState<
        number | null
    >(null)
    const descendantHeights = useMemo(() => new Map<string, number>(), [])
    const mergedRef = useMergedRefs(ref, setPopup)

    useLayoutEffect(() => {
        if (!popup) return

        const measure = (entry?: ResizeObserverEntry) => {
            const height = entry?.borderBoxSize[0]?.blockSize

            setOwnHeight(height ?? popup.offsetHeight)
        }

        measure()

        const observer = new ResizeObserver(entries => measure(entries[0]))
        observer.observe(popup, { box: 'border-box' })

        return () => observer.disconnect()
    }, [popup])

    const reportDescendantHeight = useCallback(
        (id: string, height: number | null) => {
            if (height === null) descendantHeights.delete(id)
            else descendantHeights.set(id, height)

            const heights = Array.from(descendantHeights.values())

            setFrontmostDescendantHeight(heights[heights.length - 1] ?? null)
        },
        [descendantHeights]
    )

    const frontmostHeight = frontmostDescendantHeight ?? ownHeight

    useLayoutEffect(() => {
        if (!parentReporter || frontmostHeight === 0) return

        parentReporter(dialogId, frontmostHeight)

        return () => parentReporter(dialogId, null)
    }, [dialogId, frontmostHeight, parentReporter])

    const stackStyle = {
        '--dialog-height': `${ownHeight}px`,
        '--frontmost-dialog-height': `${frontmostHeight}px`,
        ...style,
    } as DialogStackStyle

    return (
        <DialogPortal>
            <DialogOverlay />
            <DialogPrimitive.Popup
                ref={mergedRef}
                data-slot={'dialog-content'}
                style={stackStyle}
                className={cn(
                    // Values from the create-page default (base + style "nova"),
                    // source apps/v4/styles/base-nova/ui/dialog.tsx: the flat
                    // `ring-1 ring-foreground/10` on `rounded-xl bg-popover` instead
                    // of border+shadow, and `p-4` rather than shadcn's older `p-6`.
                    // Upstream caps at `sm:max-w-sm`; ours stay `sm:max-w-lg` because
                    // these hold lists and forms, not just a confirm prompt.
                    'dialog-popup bg-popover text-popover-foreground ring-foreground/10 fixed left-1/2 z-50 grid w-full max-w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2 gap-4 rounded-xl p-4 text-sm ring-1 outline-none sm:max-w-lg',
                    // A nested dialog has no backdrop of its own, so tint the
                    // measured and offset parent to make the stack legible.
                    'after:bg-foreground/5 after:pointer-events-none after:absolute after:inset-0 after:rounded-[inherit] after:opacity-0 after:transition-opacity after:duration-100 data-nested-dialog-open:after:opacity-100',
                    // Base UI drives enter/exit with data-starting-style /
                    // data-ending-style. Keyframed transforms would fight the
                    // nesting scale and offset for the same properties.
                    'transition-[opacity,scale,top] duration-100 data-ending-style:scale-95 data-ending-style:opacity-0 data-starting-style:scale-95 data-starting-style:opacity-0',
                    'motion-reduce:transition-none',
                    className
                )}
                {...props}
            >
                <DialogStackContext.Provider value={reportDescendantHeight}>
                    {children}
                </DialogStackContext.Provider>
                <DialogPrimitive.Close
                    className={
                        'text-muted-foreground hover:bg-accent hover:text-accent-foreground focus-visible:ring-ring/50 absolute top-4 right-4 flex size-5 items-center justify-center rounded-md transition-colors outline-none focus-visible:ring-3 disabled:pointer-events-none'
                    }
                >
                    <IconX className={'size-4'} />
                    <span className={'sr-only'}>Close</span>
                </DialogPrimitive.Close>
            </DialogPrimitive.Popup>
        </DialogPortal>
    )
}
DialogContent.displayName = 'DialogContent'

export default DialogContent
