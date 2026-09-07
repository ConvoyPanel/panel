import { cn } from '@/utils'
import { ReactNode } from 'react'

import { TablerIcon } from '@/lib/tabler.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'

interface Props {
    title: ReactNode
    icon: TablerIcon
    children?: ReactNode
    /**
     * What the figure above is measured against -- its denominator, and at
     * most one other fact worth the room.
     *
     * Every tile in the row fills this. A reading with nothing to read it
     * against ("5%") left the bottom half of a stretched tile empty and told
     * the reader nothing about whether the number was fine.
     */
    context?: ReactNode
    /**
     * A meter for the value above it, drawn in the rail.
     *
     * Deliberately not a `CardFooter`: that is `border-t bg-muted/50` and
     * rules the bar off from the number it measures. The rail sits closer to
     * the figure than the old in-flow bar did, and costs the tile no height.
     */
    meter?: ReactNode
    /**
     * A trend for the value above it, drawn in the rail. Unlike `meter` it
     * bleeds to the card's side edges, as on the admin overview's metric
     * tiles: it is a backdrop to the figure rather than a reading of its own.
     */
    trend?: ReactNode
    /**
     * Tighter padding for a dense row.
     *
     * The default sizing (`@sm:p-6`) is set for the two-up rows on the server
     * overview. A five-up row makes each tile ~180px wide, and at that width the roomier
     * padding is more of the card's height than the number it is wrapping. Same shell, same
     * type scale -- only the padding moves, and only where the row asks for it.
     */
    compact?: boolean
    className?: string
}

/*
 * The rail: a fixed band along the card's bottom edge that every tile
 * reserves and only some tiles draw into.
 *
 * It exists because a grid row is as tall as its tallest tile. When the trend
 * reserved a 32px floor and the meter added its own 17px in flow, the two
 * tiles carrying neither -- server state, and bandwidth on an unlimited
 * allowance -- came up short by that much, and the leftover showed up as a gap
 * with nowhere good to put it: under the caption it read as a missing element,
 * and above the caption it read as a hole in the middle of the card.
 *
 * So neither one is in flow. Every tile is title, figure, caption, rail, at
 * exactly the same height, and a tile with nothing to draw there simply has
 * the same bottom padding as its neighbours rather than a band of dead space.
 */
const RAIL = 'absolute inset-x-0 bottom-0 h-8'

const StatisticCard = ({
    title,
    icon: Icon,
    children,
    context,
    meter,
    trend,
    compact = false,
    className,
}: Props) => {
    return (
        <Card
            className={cn(
                'flex flex-col',
                /* Positioned and clipped, so the rail can be laid over the
                   card's own bottom edge and follow its rounded corners. */
                (trend || meter) && 'relative overflow-hidden',
                className
            )}
        >
            <CardHeader
                className={cn(
                    'flex flex-row items-center justify-between space-y-0 p-4 pb-2',
                    /* Roomy through the two-up rows, tight again at the
                       five-up one: past @5xl the tiles are ~180px, and at that
                       width the padding was more of the card's height than the
                       reading it wraps. */
                    !compact && '@sm:p-6 @sm:pb-2 @5xl:p-4 @5xl:pb-2'
                )}
            >
                <CardTitle className='text-label text-xs font-medium @sm:text-sm'>
                    {title}
                </CardTitle>
                <Icon className='text-muted-foreground h-4 w-4 shrink-0' />
            </CardHeader>
            <CardContent
                className={cn(
                    /* The bottom padding is the rail, and it is the same on
                       every tile whether or not anything is drawn in it. Deep
                       enough that a bar sits clear of both the caption above
                       it and the card's own edge below -- the spacing the
                       in-flow meter used to get from `mt-3` and the card's
                       padding. */
                    'flex flex-1 flex-col p-4 pt-0 pb-8',
                    !compact && '@sm:px-6 @5xl:px-4'
                )}
            >
                {children}
                {context && (
                    <p className='text-muted-foreground mt-auto truncate pt-1 text-xs tabular-nums'>
                        {context}
                    </p>
                )}
            </CardContent>

            {trend && (
                <div className={cn(RAIL, 'pointer-events-none')}>{trend}</div>
            )}
            {meter && (
                <div
                    className={cn(
                        RAIL,
                        'flex items-center px-4',
                        !compact && '@sm:px-6 @5xl:px-4'
                    )}
                >
                    {meter}
                </div>
            )}
        </Card>
    )
}

export default StatisticCard
