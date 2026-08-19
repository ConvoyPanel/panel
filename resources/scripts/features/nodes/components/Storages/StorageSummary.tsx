import {
    backendHint,
    backendLabel,
} from '@/features/nodes/storages/capacity.ts'
import { Storage } from '@/features/nodes/types.ts'
import { cn } from '@/utils'
import { IconHelpCircle } from '@tabler/icons-react'

import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/Tooltip'

/**
 * The line under a storage's name: what it is, not how full it is.
 *
 * Mirrors `anchorSummary` -- the facts that never change sit here so the
 * capacity column carries only the ones that do.
 *
 * The backend keeps the name Proxmox uses for it, with the explanation behind a
 * hover rather than in the label. Translating `dir` to "Local folder" reads
 * better right up until someone goes looking for it in the Proxmox UI, so the
 * word on screen matches the host and the tooltip carries the meaning.
 */
const StorageSummary = ({
    storage,
    className,
}: {
    storage: Storage
    className?: string
}) => {
    const label = backendLabel(storage.pveType)
    const hint = backendHint(storage.pveType)

    const flags = [
        // The Proxmox id, but only when it is not already the heading above.
        storage.displayName ? storage.name : null,
        storage.isThin
            ? storage.pveType === 'pbs'
                ? 'deduplicating'
                : 'thin'
            : null,
        storage.pveShared ? 'shared' : null,
    ].filter(Boolean) as string[]

    const before = storage.displayName ? [storage.name] : []
    const after = flags.filter(flag => flag !== storage.name)

    return (
        <span
            className={cn(
                'text-muted-foreground flex flex-wrap items-center gap-x-1 text-xs',
                className
            )}
        >
            {before.map(part => (
                <span key={part}>{part} ·</span>
            ))}

            {label && (
                <span className='inline-flex items-center gap-0.5'>
                    {label}
                    {hint && (
                        // Provider lives here rather than at the app root,
                        // matching NodeStatusIndicator: there is no global one.
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger
                                    className='text-muted-foreground/70 hover:text-foreground inline-flex cursor-help transition-colors'
                                    aria-label={`What ${label} means`}
                                >
                                    <IconHelpCircle className='size-3.5' />
                                </TooltipTrigger>
                                <TooltipContent className='max-w-64'>
                                    {hint}
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    )}
                </span>
            )}

            {after.map(part => (
                <span key={part}>· {part}</span>
            ))}
        </span>
    )
}

export default StorageSummary
