import type { Anchor } from '@/features/anchors/types.ts'
import { IconCircleCheck } from '@tabler/icons-react'

import { Badge } from '@/components/ui/Badge'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/Tooltip'

const range = (anchor: Anchor) =>
    anchor.protocolMin === anchor.protocolMax
        ? `${anchor.protocolMin}`
        : `${anchor.protocolMin}–${anchor.protocolMax}`

/**
 * Whether this build can talk to this panel, as a state rather than a sum the
 * reader has to do. "2–3 · panel speaks 1" made every healthy anchor carry the
 * arithmetic for the rare broken one; the numbers are still there, but only
 * where they explain something -- under the marker, on hover.
 *
 * The trigger is a real focusable element rather than `asChild` over a span, so
 * the explanation is reachable by keyboard (same as NodeStatusIndicator and the
 * admin nodes card).
 */
const AnchorProtocol = ({
    anchor,
    quiet,
}: {
    anchor: Anchor
    /**
     * Roster mode: show the badge when something is wrong and nothing at all
     * when it isn't. A green tick on every healthy row would be colour spent on
     * the answer "as expected", which is the opposite of what the list is for.
     */
    quiet?: boolean
}) => {
    if (quiet && anchor.compatibility !== 'incompatible') return null

    if (anchor.protocolMin === null || anchor.protocolMax === null) {
        return (
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger className='text-muted-foreground cursor-help text-sm'>
                        Not reported
                    </TooltipTrigger>
                    <TooltipContent className='max-w-64'>
                        This anchor hasn&apos;t told the panel which protocol
                        versions it speaks. It reports that on its first
                        heartbeat after enrolling.
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        )
    }

    if (anchor.compatibility === 'incompatible') {
        return (
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger className='cursor-help'>
                        <Badge variant='destructive'>Incompatible</Badge>
                    </TooltipTrigger>
                    <TooltipContent className='max-w-64'>
                        Speaks protocol {range(anchor)}, and this panel speaks{' '}
                        {anchor.panelProtocolVersion}. Console sessions will
                        fail until the two overlap — upgrade whichever side is
                        behind.
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        )
    }

    return (
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger
                    className='text-success flex cursor-help items-center gap-1.5 text-sm'
                    aria-label='Compatible with this panel'
                >
                    <IconCircleCheck className='size-4' aria-hidden />
                    Compatible
                </TooltipTrigger>
                <TooltipContent className='max-w-64'>
                    Speaks protocol {range(anchor)}, which this panel supports.
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    )
}

export default AnchorProtocol
