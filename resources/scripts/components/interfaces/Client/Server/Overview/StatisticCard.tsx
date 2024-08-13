import { cn } from '@/utils'
import { ReactNode } from 'react'

import { TablerIcon } from '@/lib/tabler.ts'

import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'


interface Props {
    title: string
    icon: TablerIcon
    children?: ReactNode
    footer?: ReactNode
    className?: string
}

const StatisticCard = ({
    title,
    icon: Icon,
    children,
    footer,
    className,
}: Props) => {
    return (
        <Card className={cn('flex flex-col', className)}>
            <CardHeader className='flex flex-row items-center justify-between space-y-0 p-4 pb-2 @sm:p-6 @sm:pb-2'>
                <CardTitle className='text-xs font-medium @sm:text-sm'>
                    {title}
                </CardTitle>
                <Icon className='h-4 w-4 text-muted-foreground' />
            </CardHeader>
            <CardContent className={'p-4 pt-0 @sm:px-6 @sm:pb-6'}>
                {children}
            </CardContent>
            {footer && (
                <CardFooter
                    className={
                        'flex grow flex-col justify-end p-4 pt-0 @sm:px-6 @sm:pb-6'
                    }
                >
                    {footer}
                </CardFooter>
            )}
        </Card>
    )
}

export default StatisticCard
