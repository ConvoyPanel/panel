import {
    type BackupQuota,
    useBackupQuota,
} from '@/features/servers/backups/api.ts'
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
 * and the reasoning live in the sheet, where there is room to say them properly.
 *
 * It always renders something: a quota control that disappears on the servers
 * with the most to explain is how "no slots allocated" became an unexplained
 * blank page. `0 of 0` is not the answer either — a limit of zero is a fact
 * about configuration, not a measurement, so it gets said in words.
 */
const summary = (quota: BackupQuota): [string, string] => {
    // A limit lowered underneath existing backups still has a count worth
    // reporting, and `3 of 0` is the honest way to say it.
    //
    // The empty unit is deliberate: the figure/unit split styles a number
    // against its noun, and splitting a plain phrase across the same two tones
    // just makes one of its words look arbitrarily grey.
    if (quota.isUnavailable && quota.count === 0) {
        return ['No quota allocated', '']
    }

    if (quota.isCountUnlimited) {
        return [`${quota.count}`, plural(quota.count)]
    }

    return [`${quota.count} of ${quota.countLimit}`, plural(quota.count)]
}

/**
 * The line under each figure. It reports what is LEFT rather than restating the
 * limit, because headroom is the number the reader is actually deciding on —
 * and the limit is already sitting inline next to the figure.
 */
const countNote = (quota: BackupQuota): string | undefined => {
    if (quota.isCountUnlimited) return 'No limit on this server'

    if (!quota.hasCountLimit) {
        return 'Your administrator can allocate backup slots to this server.'
    }

    const free = Math.max(0, quota.countLimit - quota.count)

    return free === 0
        ? 'No slots free — delete a backup to make room'
        : `${free} ${free === 1 ? 'slot' : 'slots'} free`
}

const sizeNote = (quota: BackupQuota): string | undefined => {
    if (quota.isSizeUnlimited) return 'No limit on this server'
    if (!quota.hasSizeLimit) return undefined

    const free = quota.sizeLimit - quota.size

    return free <= 0 ? 'No storage free' : `${formatBytes(free)} free`
}

interface DetailProps {
    label: string
    /** The figure, carrying the emphasis. */
    used: string
    /**
     * The denominator, set inline and dimmed rather than in a caption below.
     * A headline that needs the line under it to say what it is out of is a
     * headline you can misread on its own — which is how `0 backups` came to
     * mean both "you have used none" and "you were allocated none". Null when
     * nothing bounds the figure.
     */
    of: string | null
    /** What the figure leaves you: the headroom, or why there isn't any. */
    note?: string
    /** Null when no finite allowance exists, which drops the bar with it. */
    percent: number | null
    isExhausted?: boolean
    /** Renders `used` as a muted phrase — for states that have no figure. */
    isPhrase?: boolean
}

const Detail = ({
    label,
    used,
    of,
    note,
    percent,
    isExhausted,
    isPhrase,
}: DetailProps) => (
    <div>
        <dt className={'text-muted-foreground text-xs font-medium'}>{label}</dt>
        <dd className={'mt-1'}>
            <span
                className={cn(
                    'text-foreground block text-2xl font-semibold tracking-tight tabular-nums',
                    isExhausted && 'text-destructive',
                    isPhrase && 'text-muted-foreground text-base font-medium'
                )}
            >
                {used}
                {of && (
                    <span className={'text-muted-foreground font-medium'}>
                        {' '}
                        {of}
                    </span>
                )}
            </span>
            {note && (
                <span className={'text-muted-foreground mt-1 block text-sm'}>
                    {note}
                </span>
            )}
        </dd>
        {percent !== null && (
            <LinearProgressBar
                className={'mt-3'}
                value={percent}
                aria-label={`${percent}% used`}
                indicatorClassName={isExhausted ? 'bg-destructive' : undefined}
            />
        )}
    </div>
)

const BackupQuota = () => {
    const quota = useBackupQuota()

    if (!quota) return null

    const countPercent = percentage(quota.count, quota.countLimit)
    const sizePercent = percentage(quota.size, quota.sizeLimit)
    const [figure, unit] = summary(quota)

    return (
        <Sheet>
            <SheetTrigger
                render={
                    <Button
                        variant={'outline'}
                        className={'h-auto flex-col items-start gap-1 py-1.5'}
                        aria-label={`Backup quota: ${figure} ${unit}. Open for detail.`}
                    >
                        <span className={'text-xs tabular-nums'}>
                            <span
                                className={cn(
                                    'font-medium',
                                    quota.isAtCountLimit &&
                                        !quota.isUnavailable &&
                                        'text-destructive'
                                )}
                            >
                                {figure}
                            </span>
                            {unit && (
                                <span className={'text-muted-foreground'}>
                                    {' '}
                                    {unit}
                                </span>
                            )}
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
                <dl className={'mt-4 flex flex-col gap-8'}>
                    <Detail
                        label={'Backups'}
                        used={
                            quota.hasCountLimit || quota.isCountUnlimited
                                ? `${quota.count}`
                                : 'None allocated'
                        }
                        of={
                            quota.hasCountLimit
                                ? `of ${quota.countLimit}`
                                : null
                        }
                        note={countNote(quota)}
                        percent={quota.hasCountLimit ? countPercent : null}
                        isExhausted={quota.isAtCountLimit && quota.count > 0}
                        isPhrase={
                            !quota.hasCountLimit && !quota.isCountUnlimited
                        }
                    />
                    <Detail
                        label={'Storage'}
                        used={
                            quota.hasSizeLimit || quota.isSizeUnlimited
                                ? formatBytes(quota.size)
                                : 'None allocated'
                        }
                        of={
                            quota.hasSizeLimit
                                ? `of ${formatBytes(quota.sizeLimit)}`
                                : null
                        }
                        note={sizeNote(quota)}
                        percent={quota.hasSizeLimit ? sizePercent : null}
                        isPhrase={!quota.hasSizeLimit && !quota.isSizeUnlimited}
                    />
                </dl>
            </SheetContent>
        </Sheet>
    )
}

export default BackupQuota
