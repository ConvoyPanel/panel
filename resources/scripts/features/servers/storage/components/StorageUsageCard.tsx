import { useServer, useServerResources } from '@/features/servers/detail/api.ts'
import { formatBytes } from '@/features/servers/storage/api.ts'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { LinearProgressBar } from '@/components/ui/Progress'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    uuid: string
}

/**
 * How full the server is, and against what.
 *
 * There are two sizes here and they are not the same number: the guest agent
 * reports the filesystem it can see, while the server record holds the block
 * device Convoy provisioned. The gap between them — unpartitioned space, a
 * filesystem that has not been grown into its disk — is most of the reason
 * someone opens this page, so both are stated rather than one silently standing
 * in for the other.
 */
const StorageUsageCard = ({ uuid }: Props) => {
    const { data: server } = useServer(uuid)
    const { data: usage, isLoading, isError } = useServerResources(uuid)

    const provisioned = server?.disk ?? 0
    const hasUsage = usage !== undefined && usage.totalBytes > 0
    const percent = hasUsage
        ? Math.min(100, Math.round((usage.usedBytes / usage.totalBytes) * 100))
        : 0

    return (
        <Card>
            <CardHeader>
                <CardTitle>Filesystem usage</CardTitle>
                <CardDescription>
                    Reported live by the guest agent.
                </CardDescription>
            </CardHeader>
            <CardContent className={'flex-1'}>
                {isLoading ? (
                    <Skeleton className={'h-28 w-full'} />
                ) : isError || !hasUsage ? (
                    <div className={'flex flex-col gap-3 text-sm'}>
                        <p className={'text-muted-foreground'}>
                            The guest agent isn’t answering, so there’s nothing
                            to report on how full the filesystem is. Install and
                            start{' '}
                            <code className={'font-mono'}>
                                qemu-guest-agent
                            </code>{' '}
                            inside the server to see usage here.
                        </p>
                        {provisioned > 0 && (
                            <Row
                                label={'Provisioned'}
                                value={formatBytes(provisioned)}
                            />
                        )}
                    </div>
                ) : (
                    <div className={'flex flex-col gap-4'}>
                        <div className={'flex flex-col gap-2'}>
                            <p className={'flex items-baseline gap-2'}>
                                <span
                                    className={
                                        'text-2xl font-semibold tracking-tight tabular-nums'
                                    }
                                >
                                    {formatBytes(usage.usedBytes)}
                                </span>
                                <span
                                    className={
                                        'text-muted-foreground text-sm tabular-nums'
                                    }
                                >
                                    used · {percent}%
                                </span>
                            </p>
                            <LinearProgressBar
                                value={percent}
                                aria-label={`${percent}% of the filesystem is used`}
                            />
                        </div>

                        <div className={'flex flex-col'}>
                            <Row
                                label={'Filesystem'}
                                value={formatBytes(usage.totalBytes)}
                            />
                            <Row
                                label={'Free'}
                                value={formatBytes(
                                    Math.max(
                                        0,
                                        usage.totalBytes - usage.usedBytes
                                    )
                                )}
                            />
                            {provisioned > 0 && (
                                <Row
                                    label={'Provisioned'}
                                    value={formatBytes(provisioned)}
                                />
                            )}
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    )
}

const Row = ({ label, value }: { label: string; value: string }) => (
    <div
        className={
            'flex items-baseline justify-between border-t py-2 text-sm first:border-t-0'
        }
    >
        <span className={'text-muted-foreground'}>{label}</span>
        <span className={'font-mono tabular-nums'}>{value}</span>
    </div>
)

export default StorageUsageCard
