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
                /* Clipped so a bleeding trend follows the rounded corners. */
                trend && 'overflow-hidden',
                className
            )}
        >
            <CardHeader className='flex flex-row items-center justify-between space-y-0 p-4 pb-2 @sm:p-6 @sm:pb-2'>
                <CardTitle className='text-label text-xs font-medium @sm:text-sm'>
                    {title}
                </CardTitle>
                <Icon className='text-muted-foreground h-4 w-4 shrink-0' />
            </CardHeader>
            <CardContent
                className={cn(
                    'p-4 pt-0 @sm:px-6 @sm:pb-6',
                    /* The trend sits at the bottom however tall the row is
                       stretched, so the content above it stays put whether or
                       not a neighbouring card is taller. */
                    trend && 'flex flex-1 flex-col'
                )}
            >
                {children}
                {meter && <div className={'mt-3'}>{meter}</div>}
                {trend && (
                    /* Negative margins cancel the padding at both breakpoints,
                       which is what takes it to the card's edges. */
                    <div className='mt-3 -mr-4 -mb-4 -ml-4 h-8 @sm:-mr-6 @sm:-mb-6 @sm:-ml-6'>
                        {trend}
                    </div>
                )}
            </CardContent>
        </Card>
    )
}

export default StatisticCard
