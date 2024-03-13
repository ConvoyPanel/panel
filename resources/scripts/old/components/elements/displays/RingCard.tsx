import { Badge, RingProgress } from '@mantine/core'

import Card from '@/components/elements/Card'

interface Props {
    title: string
    value: number
    valueLabel: string | ((value: number) => string)
    total: number
    totalLabel: string | ((total: number) => string)
}

const RingCard = ({ title, value, valueLabel, total, totalLabel }: Props) => {
    const percentage = value ? Math.floor((value / total) * 10000) / 100 : 0

    return (
        <Card className='flex flex-col justify-between items-center col-span-10 lg:col-span-2'>
            <h5 className='h5'>{title}</h5>
            <div className='grid place-items-center mt-5'>
                <h4 className='absolute text-3xl font-semibold text-foreground'>
                    {Math.floor(percentage)}
                </h4>
                <RingProgress
                    size={128}
                    thickness={12}
                    roundCaps
                    sections={[
                        {
                            value: percentage,
                            color: percentage < 100 ? 'green' : 'yellow',
                        },
                    ]}
                />
            </div>
            <Badge
                className='!normal-case'
                size='lg'
                color='gray'
                variant='outline'
            >
                {value}{' '}
                {typeof valueLabel === 'function'
                    ? valueLabel(value)
                    : valueLabel}{' '}
                / {total}{' '}
                {typeof totalLabel === 'function'
                    ? totalLabel(total)
                    : totalLabel}
            </Badge>
        </Card>
    )
}

export default RingCard
