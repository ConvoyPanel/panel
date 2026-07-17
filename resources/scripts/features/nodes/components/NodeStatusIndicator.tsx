import { Node } from '@/types/node.ts'
import { cn } from '@/utils'

import { connectionErrorCopy } from '@/features/nodes/connection-errors.ts'
import { mapConnectionErrorType } from '@/lib/transformers/node.ts'

import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/Tooltip'

/**
 * A node's reachability, as of the last time `nodes:poll` checked.
 *
 * Dot tones follow the admin overview's existing convention (emerald / muted /
 * destructive) rather than introducing a second status vocabulary.
 */
const TONE: Record<Node['status'], string> = {
    online: 'bg-emerald-500',
    unreachable: 'bg-destructive',
    unknown: 'bg-muted-foreground/60',
}

const LABEL: Record<Node['status'], string> = {
    online: 'Online',
    unreachable: 'Unreachable',
    unknown: 'Unknown',
}

/**
 * Why the node reads the way it does — the whole point of the indicator, since
 * "unreachable" alone is the same unhelpful sentence the old status banner had.
 *
 * `unknown` is about Convoy, not the node: it means nothing has polled recently,
 * which on a healthy install means the scheduler or the queue worker has
 * stopped. Saying so beats implying the node is at fault.
 */
const reasonFor = (node: Node): string => {
    if (node.status === 'online') {
        return 'Convoy reached this node’s API on its last check.'
    }

    if (node.status === 'unknown') {
        return 'Convoy has not checked this node recently. Its scheduled status poll may not be running.'
    }

    return connectionErrorCopy(mapConnectionErrorType(node.statusCode ?? ''))
        .title
}

const NodeStatusIndicator = ({ node }: { node: Node }) => (
    // Provider lives here rather than at the app root, matching StorageUsageCard:
    // there is no global one to rely on.
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger className='flex cursor-default items-center gap-2 text-sm'>
                <span
                    aria-hidden
                    className={cn(
                        'size-2 shrink-0 rounded-full',
                        TONE[node.status]
                    )}
                />
                {LABEL[node.status]}
            </TooltipTrigger>
            <TooltipContent className='max-w-64'>
                {reasonFor(node)}
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
)

export default NodeStatusIndicator
