import { DeploymentStep, DeploymentStatus } from '@/types/deployment'
import { LinearProgressBar } from '@/components/ui/Progress'
import { formatDistance, formatDistanceToNow } from 'date-fns'
import { IconCheck, IconLoader, IconX } from '@tabler/icons-react'
import byteSize from 'byte-size'

interface DeploymentStepRowProps {
    step: DeploymentStep
}

const STEP_MAPPINGS: Record<string, { label: string; showProgress?: boolean; isBytes?: boolean }> = {
    'clone': { label: 'Cloning template', showProgress: true, isBytes: true },
    'configure': { label: 'Configuring server', showProgress: true, isBytes: false },
    'update-password': { label: 'Updating password', showProgress: false },
    'delete-backups': { label: 'Deleting backups', showProgress: true, isBytes: false },
    'kill-vm': { label: 'Stopping VM', showProgress: false },
    'delete-vm': { label: 'Deleting VM', showProgress: false },
}

export default function DeploymentStepRow({ step }: DeploymentStepRowProps) {
    const mapping = STEP_MAPPINGS[step.name] || { label: step.name }

    const isPending = step.status === DeploymentStatus.Pending
    const isRunning = step.status === DeploymentStatus.Running
    const isCompleted = step.status === DeploymentStatus.Completed
    const isFailed = step.status === DeploymentStatus.Failed

    const progressPercent = step.progressTotal > 0
        ? Math.round((step.progressCurrent / step.progressTotal) * 100)
        : 0

    const formatProgress = () => {
        if (mapping.isBytes && step.progressTotal > 0) {
            const current = byteSize(step.progressCurrent)
            const total = byteSize(step.progressTotal)
            return `${current.value}${current.unit} / ${total.value}${total.unit}`
        }
        return `${progressPercent}%`
    }

    return (
        <div className={`flex flex-col gap-2 p-3 rounded-lg border ${isRunning ? 'bg-secondary/50 border-primary/20' : 'bg-card border-border'} ${isPending ? 'opacity-50' : ''}`}>
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="w-6 h-6 flex items-center justify-center">
                        {isPending && <div className="w-2 h-2 rounded-full bg-muted-foreground" />}
                        {isRunning && <IconLoader className="w-4 h-4 animate-spin text-primary" />}
                        {isCompleted && <IconCheck className="w-4 h-4 text-green-500" />}
                        {isFailed && <IconX className="w-4 h-4 text-red-500" />}
                    </div>
                    <div className="flex flex-col">
                        <span className="font-medium text-sm">{mapping.label}</span>
                        {step.startedAt && (
                            <span className="text-xs text-muted-foreground">
                                {isCompleted && step.completedAt
                                    ? `Completed in ${formatDistance(step.startedAt, step.completedAt, { includeSeconds: true }).replace('less than a minute', '< 1m')}`
                                    : `Running for ${formatDistanceToNow(step.startedAt)}`
                                }
                            </span>
                        )}
                    </div>
                </div>
                {mapping.showProgress && isRunning && (
                    <span className="text-xs font-mono text-muted-foreground">
                        {formatProgress()}
                    </span>
                )}
            </div>

            {mapping.showProgress && isRunning && (
                <div className="pl-9 pr-2">
                    <LinearProgressBar value={progressPercent} className="h-1.5" />
                </div>
            )}
        </div>
    )
}
