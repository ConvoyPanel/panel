import { cn } from '@/utils'
import { ReactNode } from 'react'

import { TablerIcon } from '@/lib/tabler.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'

interface Props {
    title: ReactNode
    icon: TablerIcon
    children?: ReactNode
    /**
     * A meter for the value above it. Deliberately not a `CardFooter`: that is
     * `border-t bg-muted/50` and, in a stretched grid row, pins to the card's
     * bottom edge -- ruling the bar off from the number it measures.
     */
    meter?: ReactNode
    /**
     * A trend running along the card's bottom edge, as on the admin overview's
     * metric tiles. Unlike `meter` this bleeds: it is a backdrop to the figure
     * rather than a reading of its own, so it runs the full width and meets
     * the card's border.
     */
    trend?: ReactNode
    className?: string
}

const StatisticCard = ({
    title,
    icon: Icon,
    children,
    meter,
    trend,
    className,
}: Props) => {
    return (
        <Card
            className={cn(
                'flex flex-col',
                /* Positioned and clipped, so a bleeding trend can be laid over
                   the card's own bottom edge and follow its rounded corners. */
                trend && 'relative overflow-hidden',
                className
            )}
        >
            <CardHeader className='flex flex-row items-center justify-between space-y-0 p-4 pb-2 @sm:p-6 @sm:pb-2'>
                <CardTitle className='text-label text-xs font-medium @sm:text-sm'>
                    {title}
                </CardTitle>
                <Icon className='text-muted-foreground h-4 w-4 shrink-0' />
            </CardHeader>
            <CardContent className={'p-4 pt-0 @sm:px-6 @sm:pb-6'}>
                {children}
                {meter && <div className={'mt-3'}>{meter}</div>}
            </CardContent>

            {trend && (
                /*
                 * Out of flow entirely, laid over the card's bottom edge.
                 *
                 * In flow it added 20px to the card, and since these tiles
                 * share a stretched grid row every card grew with it -- so the
                 * two that have no trend gained a band of dead space to match.
                 * The tiles have room to spare below the figure; the trend
                 * occupies that rather than asking for more.
                 */
                <div className='pointer-events-none absolute inset-x-0 bottom-0 h-8'>
                    {trend}
                </div>
            )}
        </Card>
    )
}

export default StatisticCard
