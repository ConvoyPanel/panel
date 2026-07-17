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
    className?: string
}

const StatisticCard = ({
    title,
    icon: Icon,
    children,
    meter,
    className,
}: Props) => {
    return (
        <Card className={cn('flex flex-col', className)}>
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
        </Card>
    )
}

export default StatisticCard
