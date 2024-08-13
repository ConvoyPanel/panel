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
        <Card className={className}>
            <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
                <CardTitle className='text-sm font-medium'>{title}</CardTitle>
                <Icon className='h-4 w-4 text-muted-foreground' />
            </CardHeader>
            <CardContent>{children}</CardContent>
            {footer && <CardFooter>{footer}</CardFooter>}
        </Card>
    )
}

export default StatisticCard
