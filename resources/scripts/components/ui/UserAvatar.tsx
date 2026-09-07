import { cn } from '@/utils'
import { useEffect, useState } from 'react'

interface Props {
    name?: string | null
    src?: string | null
    className?: string
}

/** "Ada Lovelace" → "AL"; a single word gives one letter. */
const initialsOf = (name?: string | null): string =>
    (name ?? '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map(word => word[0]!.toUpperCase())
        .join('')

/**
 * An account's picture, or its initials when it has none.
 *
 * Sized entirely by the caller through `className`, so the same component fills
 * the header button, a sidebar header and a table row without a size prop that
 * has to grow an entry every time one of those changes.
 */
const UserAvatar = ({ name, src, className }: Props) => {
    const [failed, setFailed] = useState(false)

    // A new picture is a new URL, so this resets when one is uploaded — without
    // it a single broken load would keep showing initials for the session.
    useEffect(() => setFailed(false), [src])

    const initials = initialsOf(name)

    return (
        <span
            className={cn(
                'bg-muted text-muted-foreground grid size-8 shrink-0 place-items-center overflow-hidden rounded-full text-xs font-medium select-none',
                className
            )}
            aria-hidden
        >
            {src && !failed ? (
                <img
                    src={src}
                    alt=''
                    className={'size-full object-cover'}
                    onError={() => setFailed(true)}
                />
            ) : (
                initials
            )}
        </span>
    )
}

export default UserAvatar
