import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'
import { useServer } from '@/features/servers/detail/api.ts'
import { IconServerCog } from '@tabler/icons-react'
import byteSize from 'byte-size'

const SpecificationsCard = () => {
    const { data: server } = useServer()

    const memory = byteSize(server?.memory ?? 0, {
        units: 'iec',
        precision: 2,
    })
    const disk = byteSize(server?.disk ?? 0, {
        units: 'iec',
        precision: 2,
    })

    const specs = [
        {
            title: 'CPU Cores',
            value: server?.cpu ?? 'Unknown',
        },
        {
            title: 'Memory',
            value: `${memory.value} ${memory.unit}`,
        },
        {
            title: 'Disk',
            value: `${disk.value} ${disk.unit}`,
        },
    ]

    return (
        <StatisticCard
            title={'System Specifications'}
            icon={IconServerCog}
            className={'col-span-2'}
        >
            <dl className={'grid grid-cols-1 gap-3 @xl:grid-cols-3 @xl:gap-4'}>
                {/* col-reverse: the spec reads value-first, but a `dl` requires
                    its `dt` to precede the `dd` it labels. */}
                {specs.map(spec => (
                    <div key={spec.title} className={'flex flex-col-reverse'}>
                        <dt className={'text-muted-foreground text-sm'}>
                            {spec.title}
                        </dt>
                        <dd
                            className={
                                'text-lg font-semibold tracking-tight @sm:text-xl @xl:text-2xl'
                            }
                        >
                            {spec.value}
                        </dd>
                    </div>
                ))}
            </dl>
        </StatisticCard>
    )
}

export default SpecificationsCard
