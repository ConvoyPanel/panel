import { DeploymentStatus, DeploymentStep } from '@/types/deployment'
import { cn } from '@/utils'
import { IconCheck, IconLoader, IconX } from '@tabler/icons-react'
import byteSize from 'byte-size'
import { differenceInMilliseconds, intervalToDuration } from 'date-fns'
import { useEffect, useState } from 'react'

import { LinearProgressBar } from '@/components/ui/Progress'

interface DeploymentStepRowProps {
    step: DeploymentStep
    className?: string
}

const STEP_MAPPINGS: Record<
    string,
    { label: string; showProgress?: boolean; isBytes?: boolean }
> = {
    'clone': { label: 'Cloning template', showProgress: true, isBytes: true },
    'configure': {
        label: 'Configuring server',
        showProgress: true,
        isBytes: false,
    },
    'update-password': { label: 'Updating password', showProgress: false },
    'delete-backups': {
        label: 'Deleting backups',
        showProgress: true,
        isBytes: false,
    },
    'kill-vm': { label: 'Stopping VM', showProgress: false },
    'delete-vm': { label: 'Deleting VM', showProgress: false },
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

    const [now, setNow] = useState(new Date())

    useEffect(() => {
        if (!isRunning) return

        const interval = setInterval(() => {
            setNow(new Date())
        }, 100)

        return () => clearInterval(interval)
    }, [isRunning])

    const formatStepDuration = (start: Date, end: Date) => {
        const duration = intervalToDuration({ start, end })
        const ms = differenceInMilliseconds(end, start)
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
        <li
            className={cn(
                'flex flex-col gap-2 p-3',
                isRunning ? 'bg-secondary/50' : 'bg-card',
                isPending ? 'opacity-50' : '',
                className
            )}
        >
            <div className='flex items-center justify-between'>
                <div className='flex items-center gap-3'>
                    <div className='flex h-6 w-6 items-center justify-center'>
                        {isPending && (
                            <div className='h-2 w-2 rounded-full bg-muted-foreground' />
                        )}
                        {isRunning && (
                            <IconLoader className='h-4 w-4 animate-spin text-primary' />
                        )}
                        {isCompleted && (
                            <IconCheck className='h-4 w-4 text-green-500' />
                        )}
                        {isFailed && <IconX className='h-4 w-4 text-red-500' />}
                    </div>
                    <div className='flex flex-col'>
                        <span className='text-sm font-medium'>
                            {mapping.label}
                        </span>
                    </div>
                </div>
                <div className='flex flex-col'>
                    {step.startedAt && (
                        <span className='font-mono text-xs text-muted-foreground text-right'>
                            {isRunning &&
                                formatStepDuration(step.startedAt, now)}
                            {isCompleted &&
                                step.completedAt &&
                                formatStepDuration(
                                    step.startedAt,
                                    step.completedAt
                                )}
                        </span>
                    )}
                    {mapping.showProgress && isRunning && (
                        <span className='font-mono text-xs text-muted-foreground text-right'>
                            {formatProgress()}
                        </span>
                    )}
                </div>
            </div>

            {mapping.showProgress && isRunning && (
                <div className='pl-9'>
                    <LinearProgressBar
                        value={progressPercent}
                        className='h-1.5'
                    />
                </div>
            )}
        </li>
    )
}
