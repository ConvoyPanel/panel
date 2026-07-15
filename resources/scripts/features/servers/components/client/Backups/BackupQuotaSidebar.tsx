import byteSize from 'byte-size'
import { useQuery } from '@tanstack/react-query'
import { useParams } from '@tanstack/react-router'

import { backupQueries } from '@/features/servers/backups/api.ts'
import { useServer } from '@/features/servers/detail/api.ts'
import usePagination from '@/hooks/use-pagination.ts'

import { Button } from '@/components/ui/Button'
import { LinearProgressBar, RingProgress } from '@/components/ui/Progress'
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/Sheet'

// backup_count_limit / backup_size_limit both store -1 for unlimited, against
// which a percentage is meaningless.
const UNLIMITED = -1

const percentage = (used: number, limit: number) => {
    if (limit === UNLIMITED || limit <= 0) return 0

    return Math.min(100, Math.round((used / limit) * 100))
}

const formatBytes = (bytes: number) => {
    const { value, unit } = byteSize(bytes, { units: 'iec', precision: 1 })

    return `${value} ${unit}`
}

interface QuotaProps {
    label: string
    used: string
    limit: string
    value: number
}

const Quota = ({ label, used, limit, value }: QuotaProps) => (
    <div>
        <dt className={'text-sm font-medium'}>{label}</dt>
        <dd className={'mt-2 mb-4'}>
            <span className={'block text-2xl font-bold text-foreground'}>
                {used}
            </span>
            {limit}
        </dd>
        <LinearProgressBar value={value} />
    </div>
)

const BackupQuotaSidebar = () => {
    const { serverUuid } = useParams({ strict: false }) as {
        serverUuid: string
    }
    const { page } = usePagination()
    const { data: server } = useServer()
    // Same query key as BackupView, so this reads that cached response rather
    // than issuing a second request.
    const { data } = useQuery(backupQueries.list(serverUuid, { page }))

    if (!server || !data) return null

    const { countLimit, sizeLimit } = server.backup
    const countPercentage = percentage(data.backupCount, countLimit)
    const sizePercentage = percentage(data.backupSize, sizeLimit)

    return (
        <Sheet>
            <SheetTrigger
                render={
                    <Button
                        variant={'outline'}
                        className={'gap-3'}
                        aria-label={`Backup quota: ${data.backupCount} backups, ${formatBytes(data.backupSize)} used`}
                    >
                        <RingProgress
                            thickness={'xl'}
                            className={'h-6 w-6'}
                            value={countPercentage}
                        />
                        <RingProgress
                            thickness={'xl'}
                            className={'h-6 w-6'}
                            value={sizePercentage}
                        />
                    </Button>
                }
            />
            <SheetContent side={'right'}>
                <SheetHeader>
                    <SheetTitle>Backup Quota</SheetTitle>
                </SheetHeader>
                <dl className={'mt-4 flex flex-col gap-10'}>
                    <Quota
                        label={'Count'}
                        used={`${data.backupCount} ${data.backupCount === 1 ? 'backup' : 'backups'}`}
                        limit={
                            countLimit === UNLIMITED
                                ? 'of unlimited backups'
                                : `out of ${countLimit} backups`
                        }
                        value={countPercentage}
                    />
                    <Quota
                        label={'Storage Usage'}
                        used={`${formatBytes(data.backupSize)} used`}
                        limit={
                            sizeLimit === UNLIMITED
                                ? 'of unlimited storage'
                                : `out of ${formatBytes(sizeLimit)}`
                        }
                        value={sizePercentage}
                    />
                </dl>
            </SheetContent>
        </Sheet>
    )
}

export default BackupQuotaSidebar
