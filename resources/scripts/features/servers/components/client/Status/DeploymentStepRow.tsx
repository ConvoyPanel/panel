import {
    DeploymentStatus,
    DeploymentStep,
    ProgressMode,
} from '@/features/servers/types'
import { cn } from '@/utils'
import { IconCheck, IconLoader, IconX } from '@tabler/icons-react'
import byteSize from 'byte-size'
import { differenceInMilliseconds, intervalToDuration } from 'date-fns'
import { useEffect, useState } from 'react'

import { LinearProgressBar } from '@/components/ui/Progress'

interface DeploymentStepRowProps {
    step: DeploymentStep
    /** Drops the connector below the marker, so the rail ends with the list. */
    isLast?: boolean
    className?: string
}

// Whether a running step draws a determinate bar is decided by the backend
// (step.progressMode); the map only carries the display label and, for byte
// progress, how to format it.
const STEP_MAPPINGS: Record<string, { label: string; isBytes?: boolean }> = {
    'clone': { label: 'Cloning template', isBytes: true },
    'configure': { label: 'Configuring VM' },
    'update-password': { label: 'Updating password' },
    'delete-backups': { label: 'Deleting backups' },
    'stop-vm': { label: 'Stopping VM' },
    'delete-vm': { label: 'Deleting VM' },
    'start-vm': { label: 'Starting VM' },
}

/** The step's display name, for callers that need it outside a row. */
export const stepLabel = (name: string): string =>
    STEP_MAPPINGS[name]?.label ?? name

/** Whether this step reports its progress in bytes rather than as a count. */
export const stepIsBytes = (name: string): boolean =>
    STEP_MAPPINGS[name]?.isBytes === true

export const formatStepDuration = (start: Date, end: Date): string => {
    const actualEnd = end < start ? start : end
    const duration = intervalToDuration({ start, end: actualEnd })
    const ms = differenceInMilliseconds(actualEnd, start)
    const seconds = (ms / 1000) % 60

    const parts = []
    if (duration.hours) parts.push(`${duration.hours}h`)
    if (duration.minutes) parts.push(`${duration.minutes}m`)
    if (seconds > 0 || parts.length === 0) {
        parts.push(`${seconds.toFixed(1)}s`)
    }

    return parts.join(' ')
}

/**
 * One step on the deployment's rail. The connector is anchored to the marker
 * column rather than given a fixed height, so it spans whatever height the row
 * grows to — a running step with a progress bar under it included.
 */
const DeploymentStepRow = ({
    step,
    isLast,
    className,
}: DeploymentStepRowProps) => {
    const mapping = STEP_MAPPINGS[step.name] || { label: step.name }

    const isPending = step.status === DeploymentStatus.Pending
    const isRunning = step.status === DeploymentStatus.Running
    const isCompleted = step.status === DeploymentStatus.Completed
    const isFailed = step.status === DeploymentStatus.Failed
    const isDeterminate = step.progressMode === ProgressMode.Determinate

    const [now, setNow] = useState(new Date())

    useEffect(() => {
        if (!isRunning) return

        const interval = setInterval(() => {
            setNow(new Date())
        }, 100)

        return () => clearInterval(interval)
    }, [isRunning])

    const progressPercent =
        step.progressTotal > 0
            ? Math.round((step.progressCurrent / step.progressTotal) * 100)
            : 0

    const formatProgress = () => {
        if (mapping.isBytes && step.progressTotal > 0) {
            const current = byteSize(step.progressCurrent, { units: 'iec' })
            const total = byteSize(step.progressTotal, { units: 'iec' })
            return `${current.value}${current.unit} / ${total.value}${total.unit}`
        }
        return `${progressPercent}%`
    }

    return (
        <li
            className={cn(
                'grid grid-cols-[1.25rem_minmax(0,1fr)_auto] items-start gap-x-3 py-2',
                className
            )}
        >
            <span
                className={cn(
                    'relative flex w-5 justify-center self-stretch',
                    isCompleted && 'text-success',
                    isRunning && 'text-primary',
                    isFailed && 'text-destructive',
                    isPending && 'text-muted-foreground'
                )}
            >
                <span className={'flex h-5 items-center'}>
                    {isPending && (
                        <span
                            className={
                                'border-input size-2.5 rounded-full border-[1.5px]'
                            }
                        />
                    )}
                    {isRunning && (
                        <IconLoader className={'size-4 animate-spin'} />
                    )}
                    {isCompleted && <IconCheck className={'size-4'} />}
                    {isFailed && <IconX className={'size-4'} />}
                </span>
                {!isLast && (
                    <span
                        className={cn(
                            // -bottom-4 clears both this row's bottom padding
                            // and the next row's top padding (py-2 each), so
                            // the rail is continuous rather than a tick.
                            'absolute top-5 -bottom-4 left-1/2 w-px -translate-x-1/2',
                            isCompleted ? 'bg-success/40' : 'bg-border'
                        )}
                    />
                )}
            </span>

            <span
                className={cn(
                    'text-sm/5',
                    isRunning && 'font-medium',
                    isPending && 'text-muted-foreground'
                )}
            >
                {mapping.label}
            </span>

            <span
                className={
                    'text-muted-foreground text-right font-mono text-xs/5 tabular-nums'
                }
            >
                {isRunning &&
                    step.startedAt &&
                    formatStepDuration(step.startedAt, now)}
                {isCompleted &&
                    step.startedAt &&
                    step.completedAt &&
                    formatStepDuration(step.startedAt, step.completedAt)}
            </span>

            {isDeterminate && isRunning && (
                <div
                    className={
                        'col-start-2 col-end-4 flex flex-col gap-1.5 pt-1.5'
                    }
                >
                    <LinearProgressBar
                        value={progressPercent}
                        className={'bg-muted h-1'}
                    />
                    <div
                        className={
                            'text-muted-foreground flex justify-between gap-4 font-mono text-xs tabular-nums'
                        }
                    >
                        <span>{formatProgress()}</span>
                        <span>{progressPercent}%</span>
                    </div>
                </div>
            )}
        </li>
    )
}

export default DeploymentStepRow
