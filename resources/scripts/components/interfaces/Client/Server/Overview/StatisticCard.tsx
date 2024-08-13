import { ReactNode } from 'react'

import { TablerIcon } from '@/lib/tabler.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'

interface Props {
    title: string
    icon: TablerIcon
    children?: ReactNode
    className?: string
}

const StatisticCard = ({ title, icon: Icon, children, className }: Props) => {
    return (
        <Card className={className}>
            <CardHeader className='@sm:p-6 @sm:pb-2 flex flex-row items-center justify-between space-y-0 p-4 pb-2'>
                <CardTitle className='@sm:text-sm text-xs font-medium'>
                    {title}
                </CardTitle>
                <Icon className='h-4 w-4 text-muted-foreground' />
            </CardHeader>
            <CardContent className={'@sm:px-6 @sm:pb-6 p-4 pt-0'}>
                {children}
            </CardContent>
        </Card>
    )
}

export default StatisticCard
