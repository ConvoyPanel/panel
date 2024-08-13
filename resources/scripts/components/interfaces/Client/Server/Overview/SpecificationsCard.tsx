import byteSize from 'byte-size'

import useServerSWR from '@/api/servers/use-server-swr.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'


const SpecificationsCard = () => {
    const { data: server } = useServerSWR()

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
        <Card className={'col-span-2'}>
            <CardHeader>
                <CardTitle>System Specifications</CardTitle>
            </CardHeader>
            <CardContent>
                <dl className={'flex space-x-8'}>
                    {specs.map(spec => (
                        <div key={spec.title}>
                            <dt className={'text-xs text-muted-foreground'}>
                                {spec.title}
                            </dt>
                            <dd className={'text-sm'}>{spec.value}</dd>
                        </div>
                    ))}
                </dl>
            </CardContent>
        </Card>
    )
}

export default SpecificationsCard
