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

import {
    Item,
    ItemActions,
    ItemContent,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'
import { LinearProgressBar } from '@/components/ui/Progress'

interface DeploymentStepRowProps {
    step: DeploymentStep
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

export default function DeploymentStepRow({
    step,
    className,
}: DeploymentStepRowProps) {
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

    const formatStepDuration = (start: Date, end: Date) => {
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
        <Item
            role={'listitem'}
            variant={'muted'}
            size={'sm'}
            className={cn(isPending && 'opacity-50', className)}
        >
            <ItemMedia variant={'icon'}>
                {isPending && (
                    <div className='bg-muted-foreground size-2 rounded-full' />
                )}
                {isRunning && (
                    <IconLoader className='text-primary animate-spin' />
                )}
                {isCompleted && <IconCheck className='text-green-500' />}
                {isFailed && <IconX className='text-destructive' />}
            </ItemMedia>
            <ItemContent className={'min-w-0'}>
                <ItemTitle>{mapping.label}</ItemTitle>
                {isDeterminate && isRunning && (
                    <LinearProgressBar
                        value={progressPercent}
                        className='h-1.5'
                    />
                )}
                {isFailed && (step.errorCode || step.errorMessage) && (
                    <div className='text-destructive text-xs'>
                        {step.errorCode && (
                            <span className='font-mono font-bold'>
                                {step.errorCode}:{' '}
                            </span>
                        )}
                        {step.errorMessage}
                    </div>
                )}
            </ItemContent>
            <ItemActions className={'flex-col items-end gap-0'}>
                {step.startedAt && (
                    <span className='text-muted-foreground text-right font-mono text-xs'>
                        {isRunning && formatStepDuration(step.startedAt, now)}
                        {isCompleted &&
                            step.completedAt &&
                            formatStepDuration(
                                step.startedAt,
                                step.completedAt
                            )}
                    </span>
                )}
                {isDeterminate && isRunning && (
                    <span className='text-muted-foreground text-right font-mono text-xs'>
                        {formatProgress()}
                    </span>
                )}
            </ItemActions>
        </Item>
    )
}
