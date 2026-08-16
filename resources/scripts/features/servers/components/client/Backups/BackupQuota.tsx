import { type BackupQuota, useBackupQuota } from '@/features/servers/backups/api.ts'
import { cn } from '@/utils'
import byteSize from 'byte-size'

import { Button } from '@/components/ui/Button'
import { LinearProgressBar } from '@/components/ui/Progress'
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/Sheet'

const percentage = (used: number, limit: number) =>
    limit > 0 ? Math.min(100, Math.round((used / limit) * 100)) : 0

const formatBytes = (bytes: number) => {
    const { value, unit } = byteSize(bytes, { units: 'iec', precision: 1 })

    return `${value} ${unit}`
}

const plural = (count: number) => (count === 1 ? 'backup' : 'backups')

/**
 * The trigger says the one figure that gates the action — how many backups are
 * used against how many are allowed — and nothing else. Storage, percentages
 * and the unlimited/none wording live in the sheet, where there is room to say
 * them properly.
 */
const summary = (quota: BackupQuota) => {
    if (quota.isCountUnlimited) {
        return `${quota.count} ${plural(quota.count)}`
    }

    return `${quota.count} of ${quota.countLimit}`
}

interface DetailProps {
    label: string
    /** The figure, carrying the emphasis. */
    used: string
    /** What it is measured against, in words. */
    against: string
    /** Null when no finite allowance exists, which drops the bar with it. */
    percent: number | null
    isExhausted?: boolean
}

const Detail = ({
    label,
    used,
    against,
    percent,
    isExhausted,
}: DetailProps) => (
    <div>
        <dt className={'text-sm font-medium'}>{label}</dt>
        <dd className={'mt-2 mb-4'}>
            <span
                className={cn(
                    'text-foreground block text-2xl font-bold tabular-nums',
                    isExhausted && 'text-destructive'
                )}
            >
                {used}
            </span>
            <span className={'text-muted-foreground text-sm'}>{against}</span>
        </dd>
        {percent !== null && (
            <LinearProgressBar
                value={percent}
                aria-label={`${percent}% used`}
                indicatorClassName={isExhausted ? 'bg-destructive' : undefined}
            />
        )}
    </div>
)

const BackupQuota = () => {
    const quota = useBackupQuota()

    // Backups were never switched on for this server and none exist, so there
    // is no allowance to report — the empty state already says so. (A limit
    // lowered underneath existing backups still shows, as `3 of 0`.)
    if (!quota || (quota.isUnavailable && quota.count === 0)) return null

    const countPercent = percentage(quota.count, quota.countLimit)
    const sizePercent = percentage(quota.size, quota.sizeLimit)

    return (
        <Sheet>
            <SheetTrigger
                render={
                    <Button
                        variant={'outline'}
                        className={'h-auto flex-col items-start gap-1 py-1.5'}
                        aria-label={`Backup quota: ${quota.count} ${plural(quota.count)} used, ${formatBytes(quota.size)} of storage. Open for detail.`}
                    >
                        <span className={'text-xs tabular-nums'}>
                            <span
                                className={cn(
                                    'font-medium',
                                    quota.isAtCountLimit && 'text-destructive'
                                )}
                            >
                                {summary(quota)}
                            </span>{' '}
                            <span className={'text-muted-foreground'}>
                                {plural(quota.count)}
                            </span>
                        </span>
                        {quota.hasCountLimit && (
                            <LinearProgressBar
                                value={countPercent}
                                className={'h-[3px] w-full'}
                                indicatorClassName={
                                    quota.isAtCountLimit
                                        ? 'bg-destructive'
                                        : undefined
                                }
                            />
                        )}
                    </Button>
                }
            />
            <SheetContent side={'right'}>
                <SheetHeader>
                    <SheetTitle>Backup Quota</SheetTitle>
                </SheetHeader>
                <dl className={'mt-4 flex flex-col gap-10'}>
                    <Detail
                        label={'Count'}
                        used={`${quota.count} ${plural(quota.count)}`}
                        against={
                            quota.isCountUnlimited
                                ? 'of unlimited backups'
                                : quota.hasCountLimit
                                  ? `out of ${quota.countLimit} backups`
                                  : 'no backup slots are allocated to this server'
                        }
                        percent={quota.hasCountLimit ? countPercent : null}
                        isExhausted={quota.isAtCountLimit}
                    />
                    <Detail
                        label={'Storage Usage'}
                        used={`${formatBytes(quota.size)} used`}
                        against={
                            quota.isSizeUnlimited
                                ? 'of unlimited storage'
                                : quota.hasSizeLimit
                                  ? `out of ${formatBytes(quota.sizeLimit)}`
                                  : 'no backup storage is allocated to this server'
                        }
                        percent={quota.hasSizeLimit ? sizePercent : null}
                    />
                </dl>
            </SheetContent>
        </Sheet>
    )
}

export default BackupQuota
