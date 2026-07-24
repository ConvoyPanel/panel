import { cn } from '@/utils'
import { useHeadroom } from '@mantine/hooks'
import { ReactNode } from 'react'

interface Props {
    title: string
    /** Orienting line under the title. Hidden below `sm` — see below. */
    subtitle?: ReactNode
    /** Submit and cancel/discard, rendered right-aligned. */
    actions: ReactNode
}

/**
 * The sticky title + actions bar shared by the node create page and the node's
 * settings tab, so the primary action is always a flick away on both.
 */
const NodeFormToolbar = ({ title, subtitle, actions }: Props) => {
    // Headroom: the toolbar gets out of the way on the way down and comes back
    // the moment you scroll up, so the actions are always a flick away without
    // permanently eating vertical space. fixedAt keeps it put near the top,
    // where it has not started overlapping anything yet.
    const pinned = useHeadroom({ fixedAt: 120 })

    return (
        /*
         * Opaque so rows can't show through while scrolling, and mixed to
         * AppLayout's page tint (bg-muted/40 over bg-background) rather than
         * bg-background — a plain `bg-muted/40` here would composite over the
         * page's own tint and read as a darker band. `in srgb` because that is
         * where the browser alpha-composites the page's own bg-muted/40; mixing
         * in oklab lands elsewhere.
         *
         * top-14 clears the global Header, which is `sticky top-0 h-14` below
         * sm; from sm up it is `static`, so we pin to 0 there.
         */
        <div
            className={cn(
                'sticky top-14 z-10 flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b bg-[color-mix(in_srgb,var(--muted)_40%,var(--background))] py-4 transition-transform duration-200 ease-out motion-reduce:transition-none sm:top-0',
                // Unpinned it slides up by exactly its own height, which parks
                // it behind the (opaque, z-30) header on mobile and off the top
                // of the viewport on desktop.
                pinned ? 'translate-y-0' : '-translate-y-full'
            )}
        >
            {/* basis-36 is the title's floor, not its width: it still grows to
                fill the row. Below that the flex line can't fit the actions, so
                they wrap to their own row instead of crushing the title — one
                row on a phone, two on a 320px screen, without a breakpoint
                guess. */}
            <div className={'min-w-0 grow basis-36'}>
                <h1 className={'text-lg font-semibold tracking-tight'}>
                    {title}
                </h1>
                {/* Hidden below sm: on a phone this orienting line just squeezes
                    the title against the actions, and the bar is sticky, so
                    every row it costs is permanent. */}
                {subtitle && (
                    <div
                        className={
                            'text-muted-foreground hidden text-sm sm:block'
                        }
                    >
                        {subtitle}
                    </div>
                )}
            </div>
            <div className={'ml-auto flex shrink-0 items-center gap-2'}>
                {actions}
            </div>
        </div>
    )
}

export default NodeFormToolbar
