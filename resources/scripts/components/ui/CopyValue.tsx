import useClipboard from '@/hooks/use-clipboard.ts'
import { cn } from '@/utils'
import { KeyboardEvent, ReactNode } from 'react'

interface Props {
    /** Names the value in the accessible label, e.g. `UUID`. */
    label: string
    /** What lands on the clipboard. */
    value: string
    className?: string
    /** Rendered in place of `value` when the display form differs from it. */
    children?: ReactNode
}

/**
 * An identifier you can click to copy.
 *
 * The value itself is the control — no adjacent icon button. A copy icon beside
 * every UUID, MAC and address doubles the number of things in a dense list
 * without saying anything the hover state doesn't, and the values these wrap
 * are all things you copy rather than read.
 */
const CopyValue = ({ label, value, className, children }: Props) => {
    const { copy } = useClipboard({ successMessage: 'Copied to clipboard' })

    const handleKeyDown = (event: KeyboardEvent) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault()
            copy(value)
        }
    }

    return (
        <span
            role={'button'}
            tabIndex={0}
            aria-label={`Click to copy ${label} ${value}`}
            onClick={() => copy(value)}
            onKeyDown={handleKeyDown}
            className={cn(
                'hover:text-primary focus-visible:ring-ring/50 cursor-pointer rounded-sm font-mono outline-none select-none focus-visible:ring-[3px]',
                className
            )}
        >
            {children ?? value}
        </span>
    )
}

export default CopyValue
