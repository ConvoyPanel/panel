import useAnimationFrame from '@/hooks/use-animation-frame.ts'
import useMediaQuery from '@/hooks/use-media-query.ts'
import { cn } from '@/utils'
import { useRef } from 'react'

interface Props {
    value: number | undefined
    format: (value: number) => string
    className?: string
}

/**
 * A number that eases toward its target instead of snapping to it.
 *
 * Live state refetches every 50ms. Printed raw, the readout changes twenty
 * times a second and is genuinely unreadable -- the digits are a blur, and a
 * one-off spike is indistinguishable from noise. Easing toward the target acts
 * as a low-pass filter: the figure stays legible, and a real jump still arrives
 * within a few frames.
 *
 * The text is written directly to the node from the shared animation frame, so
 * a component that would otherwise re-render 20 times a second never re-renders
 * at all.
 */
const TweenedValue = ({ value, format, className }: Props) => {
    const ref = useRef<HTMLSpanElement>(null)
    const shown = useRef<number | null>(null)

    const target = useRef(value)
    target.current = value

    const formatter = useRef(format)
    formatter.current = format

    const reduced = useMediaQuery('(prefers-reduced-motion: reduce)')

    useAnimationFrame(() => {
        const node = ref.current
        const next = target.current
        if (!node || next === undefined) return

        shown.current =
            shown.current === null || reduced
                ? next
                : shown.current + (next - shown.current) * 0.16

        /* Snap the last sliver, so a value that has settled reads as an exact
           figure rather than one forever approaching it. */
        if (Math.abs(next - shown.current) < Math.abs(next) * 0.0005) {
            shown.current = next
        }

        const text = formatter.current(shown.current)
        if (node.textContent !== text) node.textContent = text
    })

    return (
        <span
            ref={ref}
            className={cn('tabular-nums', className)}
            /* The tween is decoration; assistive tech should hear the real
               figure, not whichever frame it happens to catch. */
            aria-label={value === undefined ? undefined : format(value)}
        />
    )
}

export default TweenedValue
