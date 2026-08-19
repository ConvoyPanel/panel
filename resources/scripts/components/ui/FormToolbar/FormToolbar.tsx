import { cn } from '@/utils'
import { useHeadroom } from '@mantine/hooks'
import { ReactNode, useEffect, useRef, useState } from 'react'

interface Props {
    title: string
    /** Orienting line under the title. Hidden below `sm` — see below. */
    subtitle?: ReactNode
    /** Submit and cancel/discard, rendered right-aligned. */
    actions: ReactNode
}

/**
 * The sticky title + actions bar for a full-page form — node create, node
 * settings, server create — so the primary action is always a flick away
 * however long the form runs.
 */
const FormToolbar = ({ title, subtitle, actions }: Props) => {
    // Headroom: the toolbar gets out of the way on the way down and comes back
    // the moment you scroll up, so the actions are always a flick away without
    // permanently eating vertical space. fixedAt keeps it put near the top,
    // where it has not started overlapping anything yet.
    const pinned = useHeadroom({ fixedAt: 120 })

    const barRef = useRef<HTMLDivElement>(null)
    const sentinelRef = useRef<HTMLDivElement>(null)
    const [stuck, setStuck] = useState(false)

    /*
     * Watch a hairline sentinel sitting at the bar's resting position to learn
     * when the bar has actually left the flow and started overlapping content
     * — the only moment the shadow has anything to cast onto. An observer
     * rather than a scroll handler so this doesn't re-render on every frame of
     * a scroll.
     */
    useEffect(() => {
        const bar = barRef.current
        const sentinel = sentinelRef.current

        if (!bar || !sentinel) return

        let observer: IntersectionObserver | undefined

        const observe = () => {
            observer?.disconnect()

            // The pin offset is responsive (top-14 below sm, top-0 from sm up),
            // so read it back off the element instead of restating the
            // breakpoint here and letting the two drift apart.
            const offset = parseFloat(getComputedStyle(bar).top) || 0

            observer = new IntersectionObserver(
                ([entry]) => setStuck(!entry.isIntersecting),
                { rootMargin: `-${offset}px 0px 0px 0px` }
            )

            observer.observe(sentinel)
        }

        observe()
        window.addEventListener('resize', observe)

        return () => {
            observer?.disconnect()
            window.removeEventListener('resize', observe)
        }
    }, [])

    return (
        <>
            {/* h-px so the observer has a non-zero box to intersect, -mb-px so
                it costs the layout nothing. */}
            <div ref={sentinelRef} aria-hidden className={'-mb-px h-px'} />
            {/*
             * top-14 clears the global Header, which is `sticky top-0 h-14`
             * below sm; from sm up it is `static`, so we pin to 0 there.
             */}
            <div
                ref={barRef}
                className={cn(
                    'sticky top-14 z-10 flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b py-4 transition-transform duration-200 ease-out motion-reduce:transition-none sm:top-0',
                    /*
                     * The opaque backdrop is a bleeding pseudo-element rather
                     * than a background on the bar itself. The cards below carry
                     * `ring-1`, which paints *outside* their border box, and the
                     * bar is exactly as wide as they are — so a background
                     * stopping at the bar's own edges left a hairline of ring
                     * showing down each side as the cards scrolled under it.
                     *
                     * Colour is mixed to AppLayout's page tint (bg-muted/40 over
                     * bg-background) rather than bg-background — a plain
                     * `bg-muted/40` would composite over the page's own tint and
                     * read as a darker band. `in srgb` because that is where the
                     * browser alpha-composites the page's own bg-muted/40;
                     * mixing in oklab lands elsewhere.
                     *
                     * inset-y-0 stops at the padding box, so it covers the ring
                     * without painting over the bar's own bottom border — the
                     * divider stays aligned to the card column. The bar is
                     * sticky, hence already positioned: no `relative`, which
                     * would fight the `sticky` class.
                     */
                    'before:absolute before:inset-y-0 before:-right-1 before:-left-1 before:-z-10 before:bg-[color-mix(in_srgb,var(--muted)_40%,var(--background))]',
                    /*
                     * A hint of depth under the divider, so rows passing beneath
                     * dissolve rather than getting cut dead at the border.
                     *
                     * A gradient strip rather than a box-shadow: a shadow casts
                     * in every direction, so it curled around the bar's ends and
                     * left two smudges climbing its sides. This only ever falls
                     * downward, and `inset-x-0` keeps it exactly as wide as the
                     * divider it sits under — no bleed, unlike the backdrop
                     * above, since below the bar the cards' own ring is meant to
                     * be seen.
                     *
                     * top-full lands on the *padding* box, i.e. above the 1px
                     * border, hence the +1px. pointer-events-none because it
                     * overlays the top of whatever card is underneath.
                     *
                     * Only once the bar is stuck: at rest there is nothing
                     * beneath it to cast onto and it would just be a smudge on
                     * the page. Dark mode needs a much heavier value — this one
                     * lands as black on near-black and vanishes.
                     */
                    'after:pointer-events-none after:absolute after:inset-x-0 after:top-[calc(100%+1px)] after:h-2 after:bg-[linear-gradient(to_bottom,rgb(0_0_0/0.05),transparent)] after:transition-opacity after:duration-200 motion-reduce:after:transition-none dark:after:bg-[linear-gradient(to_bottom,rgb(0_0_0/0.28),transparent)]',
                    stuck ? 'after:opacity-100' : 'after:opacity-0',
                    // Unpinned it slides up by exactly its own height, which
                    // parks it behind the (opaque, z-30) header on mobile and
                    // off the top of the viewport on desktop.
                    pinned ? 'translate-y-0' : '-translate-y-full'
                )}
            >
                {/* basis-36 is the title's floor, not its width: it still grows
                    to fill the row. Below that the flex line can't fit the
                    actions, so they wrap to their own row instead of crushing
                    the title — one row on a phone, two on a 320px screen,
                    without a breakpoint guess. */}
                <div className={'min-w-0 grow basis-36'}>
                    <h1 className={'text-lg font-semibold tracking-tight'}>
                        {title}
                    </h1>
                    {/* Hidden below sm: on a phone this orienting line just
                        squeezes the title against the actions, and the bar is
                        sticky, so every row it costs is permanent. */}
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
        </>
    )
}

export default FormToolbar
