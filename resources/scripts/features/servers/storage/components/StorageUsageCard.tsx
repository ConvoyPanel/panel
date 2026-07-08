import byteSize from 'byte-size'

import { useStorageUsage } from '@/features/servers/storage/api.ts'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { LinearProgressBar } from '@/components/ui/Progress'
import Skeleton from '@/components/ui/Skeleton.tsx'

const format = (bytes: number) => {
    const size = byteSize(bytes, { units: 'iec', precision: 1 })
    return `${size.value} ${size.unit}`
}

interface Props {
    uuid: string
}

const StorageUsageCard = ({ uuid }: Props) => {
    const { data: usage, isLoading, isError } = useStorageUsage(uuid)

    const hasUsage = usage && usage.totalBytes > 0
    const percent = hasUsage
        ? Math.min(100, Math.round((usage.usedBytes / usage.totalBytes) * 100))
        : 0

    return (
        <Card>
            <CardHeader>
                <CardTitle>Disk Usage</CardTitle>
                <CardDescription>
                    Live filesystem usage reported by the guest agent.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {isLoading ? (
                    <Skeleton className={'h-16 w-full'} />
                ) : isError || !hasUsage ? (
                    <p className={'text-sm text-muted-foreground'}>
                        Usage is unavailable — the guest agent may not be
                        running on this server.
                    </p>
                ) : (
                    <div className={'space-y-2'}>
                        <div
                            className={
                                'flex items-baseline justify-between text-sm'
                            }
                        >
                            <span className={'font-medium'}>
                                {format(usage.usedBytes)} used
                            </span>
                            <span className={'text-muted-foreground'}>
                                of {format(usage.totalBytes)} ({percent}%)
                            </span>
                        </div>
                        <LinearProgressBar value={percent} />
                    </div>
                )}
            </CardContent>
        </Card>
    )
}

export default StorageUsageCard
